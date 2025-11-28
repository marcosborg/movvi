<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use App\Models\TvdeWeek;
use Carbon\Carbon;

class SalesController extends Controller
{
    use Reports;

    public function salesByWeek(Request $request, $date)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // $date vem da rota: /api/v1/sales-by-week/03-11-2025
        $dateString = $date;

        try {
            // adapta se o teu config('panel.date_format') for outro
            $carbonDate = Carbon::createFromFormat('d-m-Y', $dateString);
        } catch (\Exception $e) {
            return response()->json([
                'error'    => 'Formato de data inválido. Usa d-m-Y, por exemplo 03-11-2025.',
                'received' => $dateString,
            ], 422);
        }

        // Formato que está no DB (por causa do setStartDateAttribute)
        $dbDate = $carbonDate->format('Y-m-d');

        // Aqui usamos o campo start_date da tabela tvde_weeks
        $tvdeWeek = TvdeWeek::where('start_date', $dbDate)->first();

        if (! $tvdeWeek) {
            return response()->json([
                'error'      => 'Semana TVDE não encontrada para a data indicada.',
                'start_date' => $dbDate,
            ], 404);
        }

        $tvde_week_id = $tvdeWeek->id;

        // 1 = company_id (ajusta se for dinâmico)
        $results = $this->getWeekReport(1, $tvde_week_id);

        // Devolver tudo em JSON
        return response()->json([
            'requested_date' => $dateString,                // o que veio na URL
            'start_date'     => $tvdeWeek->start_date,      // já vem em config('panel.date_format')
            'end_date'       => $tvdeWeek->end_date,
            'tvde_week_id'   => $tvde_week_id,
            'data'           => $results,
        ]);
    }
}
