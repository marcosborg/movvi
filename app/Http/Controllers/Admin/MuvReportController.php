<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Muv\MuvFinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MuvReportController extends Controller
{
    public function __construct(
        protected MuvFinancialReportService $reportService
    ) {
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('muv_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $report = $this->reportService->build($this->filters($request));

        return view('admin.muv.index', compact('report'));
    }

    public function pdf(Request $request)
    {
        abort_if(Gate::denies('muv_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $report = $this->reportService->build($this->filters($request));
        $filename = sprintf(
            'muv-relatorio-investidores-%s-%s.pdf',
            $report['filters']['start_date'],
            $report['filters']['end_date']
        );

        return Pdf::loadView('admin.muv.pdf', compact('report'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? Carbon::now()->startOfYear()->toDateString(),
            'end_date' => $validated['end_date'] ?? Carbon::now()->toDateString(),
            'company_id' => $validated['company_id'] ?? null,
        ];
    }
}
