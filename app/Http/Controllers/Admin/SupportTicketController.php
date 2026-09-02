<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $staff = $this->isStaff($request->user());
        $query = SupportTicket::with(['company', 'opener', 'assignee'])->latest('last_message_at');

        if (! $staff) {
            $query->where('company_id', $this->companyId($request->user()));
        }

        if ($request->filled('status') && array_key_exists($request->status, SupportTicket::STATUS_LABELS)) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('admin.supportTickets.index', [
            'tickets' => $tickets,
            'statuses' => SupportTicket::STATUS_LABELS,
            'isStaff' => $staff,
        ]);
    }

    public function create(Request $request)
    {
        abort_if($this->isStaff($request->user()), 403);
        abort_unless($this->companyId($request->user()), 403, 'O utilizador não está associado a uma empresa.');

        return view('admin.supportTickets.create');
    }

    public function store(Request $request)
    {
        abort_if($this->isStaff($request->user()), 403);
        $companyId = $this->companyId($request->user());
        abort_unless($companyId, 403, 'O utilizador não está associado a uma empresa.');
        $data = $this->validateMessage($request, true);

        $ticket = DB::transaction(function () use ($request, $companyId, $data) {
            $ticket = SupportTicket::create([
                'company_id' => $companyId,
                'opened_by' => $request->user()->id,
                'subject' => $data['subject'],
                'status' => SupportTicket::STATUS_AWAITING_TECHNICAL,
                'last_message_at' => now(),
            ]);
            $message = $ticket->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => $data['message'],
            ]);
            $this->storeAttachments($request, $message);
            return $ticket;
        });

        return redirect()->route('admin.support-tickets.show', $ticket)
            ->with('message', 'Ticket aberto. A equipa técnica foi notificada.');
    }

    public function show(Request $request, SupportTicket $supportTicket)
    {
        $this->authorizeTicket($request->user(), $supportTicket);
        $supportTicket->load(['company', 'opener', 'assignee', 'messages.sender.roles', 'messages.attachments']);

        return view('admin.supportTickets.show', [
            'ticket' => $supportTicket,
            'isStaff' => $this->isStaff($request->user()),
            'admins' => $this->admins(),
        ]);
    }

    public function reply(Request $request, SupportTicket $supportTicket)
    {
        $this->authorizeTicket($request->user(), $supportTicket);
        abort_if($supportTicket->status === SupportTicket::STATUS_CLOSED, 422, 'Este ticket está encerrado.');
        $data = $this->validateMessage($request);
        $staff = $this->isStaff($request->user());

        DB::transaction(function () use ($request, $supportTicket, $data, $staff) {
            $message = $supportTicket->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => $data['message'],
            ]);
            $this->storeAttachments($request, $message);
            $supportTicket->update([
                'assigned_to' => $staff ? ($supportTicket->assigned_to ?: $request->user()->id) : $supportTicket->assigned_to,
                'status' => $staff ? SupportTicket::STATUS_AWAITING_CUSTOMER : SupportTicket::STATUS_AWAITING_TECHNICAL,
                'last_message_at' => now(),
            ]);
        });

        return back()->with('message', 'Resposta enviada.');
    }

    public function assign(Request $request, SupportTicket $supportTicket)
    {
        abort_unless($this->isStaff($request->user()), 403);
        $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        if ($request->filled('assigned_to')) {
            abort_unless($this->isStaff(User::findOrFail($request->assigned_to)), 422, 'Selecione um administrador.');
        }
        $supportTicket->update(['assigned_to' => $request->assigned_to]);

        return back()->with('message', 'Responsável atualizado.');
    }

    public function close(Request $request, SupportTicket $supportTicket)
    {
        $this->authorizeTicket($request->user(), $supportTicket);
        if ($supportTicket->status !== SupportTicket::STATUS_CLOSED) {
            $supportTicket->update([
                'status' => SupportTicket::STATUS_CLOSED,
                'closed_by' => $request->user()->id,
                'closed_at' => now(),
            ]);
        }

        return back()->with('message', 'Ticket encerrado.');
    }

    public function attachment(Request $request, SupportTicketAttachment $attachment)
    {
        $attachment->load('message.ticket');
        $this->authorizeTicket($request->user(), $attachment->message->ticket);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
        ]);
    }

    private function validateMessage(Request $request, bool $withSubject = false): array
    {
        $rules = [
            'message' => ['required', 'string', 'max:10000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
        if ($withSubject) $rules['subject'] = ['required', 'string', 'max:160'];
        return $request->validate($rules);
    }

    private function storeAttachments(Request $request, $message): void
    {
        foreach ($request->file('images', []) as $file) {
            $path = $file->storeAs('support-tickets/'.$message->support_ticket_id, Str::uuid().'.'.$file->extension(), 'local');
            $message->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function authorizeTicket(User $user, SupportTicket $ticket): void
    {
        abort_unless($this->isStaff($user) || $ticket->company_id === $this->companyId($user), 403);
    }

    private function companyId(User $user): ?int
    {
        return optional($user->company)->id;
    }

    private function isStaff(?User $user): bool
    {
        return $user
            && ($user->hasRole('Admin') || $user->hasRole('Gestor'))
            && ! $user->company()->exists();
    }

    private function admins()
    {
        return User::whereDoesntHave('company')
            ->whereHas('roles', fn ($query) => $query->whereIn('title', ['Admin', 'Gestor']))
            ->orderBy('name')->get(['id', 'name']);
    }
}
