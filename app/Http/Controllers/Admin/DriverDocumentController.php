<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DriverDocumentController extends Controller
{
    public function index()
    {
        [$driver, $canManageAll] = $this->resolveDriverContext();

        abort_if(!$driver && !$canManageAll, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = $canManageAll ? Driver::orderBy('name')->get() : collect();
        $documents = DriverDocument::with('driver')
            ->when($driver, fn ($query) => $query->where('driver_id', $driver->id))
            ->when($canManageAll && request('driver_id'), fn ($query) => $query->where('driver_id', request('driver_id')))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.driverDocuments.index', [
            'documents' => $documents,
            'driver' => $driver,
            'drivers' => $drivers,
            'canManageAll' => $canManageAll,
            'selectedDriverId' => request('driver_id', $driver?->id),
        ]);
    }

    public function store(Request $request)
    {
        [$driver, $canManageAll] = $this->resolveDriverContext();

        abort_if(!$driver && !$canManageAll, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $targetDriver = $canManageAll
            ? Driver::findOrFail($validated['driver_id'] ?? $driver?->id)
            : $driver;

        $path = $request->file('file')->store('driver-documents', 'public');

        DriverDocument::create([
            'driver_id' => $targetDriver->id,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'file_path' => $path,
        ]);

        return redirect()->route('admin.driver-documents.index', $canManageAll && $targetDriver ? ['driver_id' => $targetDriver->id] : [])
            ->with('message', 'Documento carregado com sucesso.');
    }

    public function destroy(DriverDocument $driverDocument)
    {
        $this->authorizeDocument($driverDocument);

        Storage::disk('public')->delete($driverDocument->file_path);
        $driverDocument->delete();

        return back()->with('message', 'Documento removido com sucesso.');
    }

    public function download(DriverDocument $driverDocument)
    {
        $this->authorizeDocument($driverDocument);

        abort_if(!Storage::disk('public')->exists($driverDocument->file_path), Response::HTTP_NOT_FOUND, 'File not found');

        return Storage::disk('public')->download($driverDocument->file_path, basename($driverDocument->file_path));
    }

    protected function authorizeDocument(DriverDocument $driverDocument): void
    {
        [$driver, $canManageAll] = $this->resolveDriverContext();

        abort_if(!$canManageAll && (!$driver || (int) $driverDocument->driver_id !== (int) $driver->id), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    protected function resolveDriverContext(): array
    {
        $user = auth()->user();
        $canManageAll = (bool) ($user?->is_admin || $user?->hasRole('Admin') || $user?->hasRole('Administrador'));
        $driver = $user ? $user->driver()->first() : null;

        return [$driver, $canManageAll];
    }
}
