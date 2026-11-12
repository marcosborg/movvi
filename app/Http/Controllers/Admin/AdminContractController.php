<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAdminContractRequest;
use App\Http\Requests\StoreAdminContractRequest;
use App\Http\Requests\UpdateAdminContractRequest;
use App\Models\AdminContract;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\ContractTemplate;
use App\Services\Contracts\ContractGenerationService;
use Illuminate\Support\Str;

class AdminContractController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('admin_contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $adminContracts = AdminContract::with(['driver'])->get();

        return view('admin.adminContracts.index', compact('adminContracts'));
    }

    public function create(ContractGenerationService $generationService)
    {
        abort_if(Gate::denies('admin_contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::orderBy('name')->get();
        $template = $generationService->activeTemplate();
        $selectedDriverId = request('driver_id');
        $selectedDriver = $selectedDriverId ? Driver::find($selectedDriverId) : null;
        $defaults = $selectedDriver ? $generationService->formDefaults($selectedDriver) : [];

        return view('admin.adminContracts.create', compact('defaults', 'drivers', 'template', 'selectedDriverId'));
    }

    public function store(StoreAdminContractRequest $request, ContractGenerationService $generationService)
    {
        $driver = Driver::findOrFail($request->integer('driver_id'));
        $template = $request->filled('contract_template_id')
            ? ContractTemplate::findOrFail($request->integer('contract_template_id'))
            : null;
        $adminContract = $generationService->generate($driver, $template, $request->only([
            'weekly_cedencia',
            'commission_percent',
            'vehicle_year',
            'document_expiries',
        ]));

        if ($request->filled('signed_at')) {
            $adminContract->signed_at = $request->input('signed_at');
            $adminContract->save();
        }

        return redirect()
            ->route('admin.admin-contracts.show', $adminContract)
            ->with('message', 'Contrato gerado e arquivado com sucesso.');
    }

    public function generateForDriver(Driver $driver, ContractGenerationService $generationService)
    {
        abort_if(Gate::denies('admin_contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return redirect()->route('admin.admin-contracts.create', ['driver_id' => $driver->id]);
    }

    public function edit(AdminContract $adminContract)
    {
        abort_if(Gate::denies('admin_contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::all();

        $adminContract->load('driver');

        return view('admin.adminContracts.edit', compact('adminContract', 'drivers'));
    }

    public function update(UpdateAdminContractRequest $request, AdminContract $adminContract)
    {
        $adminContract->update($adminContract->rendered_content
            ? $request->only('signed_at')
            : $request->all());

        return redirect()->route('admin.admin-contracts.index');
    }

    public function show(AdminContract $adminContract)
    {
        // A user who can generate a contract must also be able to open the
        // immutable PDF they have just created. Keep the dedicated show
        // permission for read-only roles, while avoiding a dead-end after
        // generation for operational roles.
        abort_if(
            Gate::denies('admin_contract_show') && Gate::denies('admin_contract_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $adminContract->load('driver');

        setlocale(LC_TIME, 'pt_PT.utf8');
        Carbon::setLocale('pt_PT');

        $pdf = Pdf::loadView('admin.adminContracts.show', [
            'adminContract' => $adminContract,
        ])->setOption([
            'isRemoteEnabled' => true,
            'enable_html5_parser' => true,
        ]);

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $snapshot = $adminContract->data_snapshot ?? [];
        $companyName = $snapshot['company.name'] ?? 'ADELMO TOP UNIPESSOAL, LDA';
        $companyVat = $snapshot['company.vat'] ?? '515025712';

        $canvas->page_text(46, 813, $companyName . ' | NIF ' . $companyVat, $font, 7.5, [0.45, 0.45, 0.45]);
        $canvas->page_text(474, 813, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7.5, [0.45, 0.45, 0.45]);

        $driverName = Str::slug(optional($adminContract->driver)->name ?: 'motorista', '_');
        $filename = sprintf('Contrato_%s_%s.pdf', $adminContract->number, $driverName);

        return $pdf->stream($filename);

        //return $pdf->download($adminContract->created_at . '.pdf');

        //return view('admin.adminContracts.show', compact('adminContract'));
    }

    public function destroy(AdminContract $adminContract)
    {
        abort_if(Gate::denies('admin_contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $adminContract->delete();

        return back();
    }

    public function massDestroy(MassDestroyAdminContractRequest $request)
    {
        $adminContracts = AdminContract::find(request('ids'));

        foreach ($adminContracts as $adminContract) {
            $adminContract->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
