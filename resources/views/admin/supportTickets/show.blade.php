@extends('layouts.admin')
@section('content')
<style>
.ticket-message{display:flex;margin:18px 0}.ticket-message.mine{justify-content:flex-end}.ticket-bubble{max-width:78%;padding:14px 16px;border-radius:12px;background:#f2f4f7;border:1px solid #e2e5e9}.ticket-message.mine .ticket-bubble{background:#eeeafd;border-color:#d7d0f5}.ticket-meta{font-size:12px;color:#777;margin-bottom:7px}.ticket-text{white-space:normal;overflow-wrap:anywhere}.ticket-images{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.ticket-images img{width:150px;height:110px;object-fit:cover;border-radius:6px;border:1px solid #ddd}.ticket-status{font-size:13px;padding:6px 10px}
</style>
<div class="content">
    <div class="row">
        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $ticket->number }} — {{ $ticket->subject }}</h3>
                    <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-default btn-sm pull-right"><i class="fa fa-arrow-left"></i> Voltar</a>
                </div>
                <div class="box-body">
                    @foreach($ticket->messages as $ticketMessage)
                        @php($mine = $ticketMessage->sender_id === auth()->id())
                        <div class="ticket-message {{ $mine ? 'mine' : '' }}">
                            <div class="ticket-bubble">
                                <div class="ticket-meta"><strong>{{ $ticketMessage->sender->name }}</strong> · {{ $ticketMessage->created_at->format('d/m/Y H:i') }}</div>
                                <div class="ticket-text">{!! nl2br(e($ticketMessage->message)) !!}</div>
                                @if($ticketMessage->attachments->isNotEmpty())
                                    <div class="ticket-images">
                                        @foreach($ticketMessage->attachments as $attachment)
                                            <a href="{{ route('admin.support-tickets.attachment', $attachment) }}" target="_blank" title="{{ $attachment->original_name }}">
                                                <img src="{{ route('admin.support-tickets.attachment', $attachment) }}" alt="{{ $attachment->original_name }}">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($ticket->status !== 'closed')
                    <div class="box-footer">
                        <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                                <label for="message">A sua resposta</label>
                                <textarea id="message" name="message" class="form-control" rows="5" maxlength="10000" required>{{ old('message') }}</textarea>
                                @if($errors->has('message'))<span class="help-block">{{ $errors->first('message') }}</span>@endif
                            </div>
                            <div class="form-group">
                                <label for="images">Anexar imagens <small class="text-muted">(até 5)</small></label>
                                <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                            </div>
                            <button class="btn btn-primary"><i class="fa fa-paper-plane"></i> Enviar resposta</button>
                        </form>
                    </div>
                @else
                    <div class="box-footer text-center text-muted"><i class="fa fa-lock"></i> Este ticket está encerrado.</div>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="box box-default"><div class="box-body">
                <p><strong>Estado</strong><br><span class="label ticket-status {{ $ticket->status === 'closed' ? 'label-default' : ($ticket->status === 'awaiting_technical' ? 'label-warning' : 'label-info') }}">{{ $ticket->status_label }}</span></p>
                <p><strong>Cliente</strong><br>{{ optional($ticket->company)->name }}</p>
                <p><strong>Aberto por</strong><br>{{ optional($ticket->opener)->name }}</p>
                @if($isStaff)
                    <form method="POST" action="{{ route('admin.support-tickets.assign', $ticket) }}" style="margin:15px 0">
                        @csrf @method('PATCH')
                        <label for="assigned_to">Responsável</label>
                        <select id="assigned_to" name="assigned_to" class="form-control" onchange="this.form.submit()">
                            <option value="">Por atribuir</option>
                            @foreach($admins as $admin)<option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>@endforeach
                        </select>
                    </form>
                @else
                    <p><strong>Responsável</strong><br>{{ optional($ticket->assignee)->name ?: 'Equipa técnica' }}</p>
                @endif
                @if($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('admin.support-tickets.close', $ticket) }}" onsubmit="return confirm('Encerrar este ticket? Depois de encerrado já não será possível responder.')">
                        @csrf @method('PATCH')
                        <button class="btn btn-default btn-block"><i class="fa fa-check"></i> Encerrar ticket</button>
                    </form>
                @endif
            </div></div>
        </div>
    </div>
</div>
@endsection
