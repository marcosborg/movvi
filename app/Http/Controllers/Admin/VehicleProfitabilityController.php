<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\Reports;
use App\Models\VehicleItem;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use App\Models\VehicleExpense;
use App\Models\CurrentAccount;
use App\Models\ExpenseReimbursement;
use App\Models\Receipt;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleProfitabilityController extends Controller
{
    use Reports;

    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // ---- Filtros vindos da query ----
        $period     = $request->input('period', 'week');     // week|month|year|custom
        $year       = $request->input('year');               // ex: 2025
        $month      = $request->input('month');              // 1..12
        $weeksIds   = (array) $request->input('weeks', []);  // array de IDs de semanas
        $startDate  = $request->input('start_date');         // YYYY-MM-DD
        $endDate    = $request->input('end_date');           // YYYY-MM-DD
        $groupBy    = $request->input('group_by', 'week');   // week|month|year

        // ---- Viatura selecionada (mantém sessão como já fazias) ----
        $vehicle_items   = VehicleItem::with('driver')->get();
        $vehicle_item_id = session('vehicle_item_id', optional(VehicleItem::first())->id ?? 0);
        $vehicle_item    = VehicleItem::find($vehicle_item_id);

        // ---- Determinar semanas alvo conforme o período ----
        $weeksQuery = TvdeWeek::query();

        switch ($period) {
            case 'year':
                if ($year) {
                    $weeksQuery->whereYear('start_date', $year);
                }
                break;

            case 'month':
                if ($year)  { $weeksQuery->whereYear('start_date', $year); }
                if ($month) { $weeksQuery->whereMonth('start_date', $month); }
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    $weeksQuery->whereDate('start_date', '>=', $startDate)
                               ->whereDate('end_date',   '<=', $endDate);
                }
                break;

            case 'week':
            default:
                if (!empty($weeksIds)) {
                    $weeksQuery->whereIn('id', $weeksIds);
                } else {
                    // se nada vier selecionado, por defeito usa a semana mais recente
                    $last = TvdeWeek::orderBy('start_date', 'desc')->first();
                    if ($last) {
                        $weeksQuery->where('id', $last->id);
                    }
                }
                break;
        }

        $weeks = $weeksQuery->orderBy('start_date')->get();

        // Se não houver viatura ou semanas, devolve página “vazia” com filtros
        if (!$vehicle_item || $weeks->isEmpty()) {
            // Listas auxiliares para o formulário (a partir das datas)
            $tvde_years  = TvdeWeek::selectRaw('YEAR(start_date) as y')->distinct()->orderBy('y', 'desc')->pluck('y');
            $tvde_months = TvdeWeek::when($year, fn($q) => $q->whereYear('start_date', $year))
                                   ->selectRaw('MONTH(start_date) as m')->distinct()->orderBy('m')->pluck('m');
            $tvde_weeks  = TvdeWeek::orderBy('start_date', 'desc')->get();

            return view('admin.vehicleProfitabilities.index', [
                'vehicle_items' => $vehicle_items,
                'vehicle_item_id' => $vehicle_item_id,
                'period' => $period,
                'groupBy' => $groupBy,
                'year' => $year,
                'month' => $month,
                'tvde_years' => $tvde_years,
                'tvde_months' => $tvde_months,
                'tvde_weeks' => $tvde_weeks,
                'weeks' => collect(),
                'rows' => [],
                'groups' => [],
                'totals' => ['treasury' => 0, 'taxes' => 0, 'final' => 0],
            ]);
        }

        // ---- Cálculo por semana (reutilizável) ----
        $rows = [];
        foreach ($weeks as $week) {
            $rows[] = $this->computeWeekMetrics($vehicle_item, $week);
        }

        // ---- Agrupar por week|month|year ----
        $groups = collect($rows)->groupBy(function ($row) use ($groupBy) {
            if ($groupBy === 'year')  return $row['year']; // 2025
            if ($groupBy === 'month') return sprintf('%04d-%02d', $row['year'], $row['month']); // 2025-06
            return $row['week']->id; // week
        })->map(function ($items) {
            return [
                'treasury' => collect($items)->sum('total_treasury'),
                'taxes'    => collect($items)->sum('total_taxes'),
                'final'    => collect($items)->sum('final_total'),
                'weeks'    => $items,
            ];
        });

        // ---- Totais globais do período ----
        $totals = [
            'treasury' => $groups->sum('treasury'),
            'taxes'    => $groups->sum('taxes'),
            'final'    => $groups->sum('final'),
        ];

        // ---- Listas auxiliares para o formulário (anos/meses/semanas) ----
        $tvde_years  = TvdeWeek::selectRaw('YEAR(start_date) as y')->distinct()->orderBy('y', 'desc')->pluck('y');
        $tvde_months = TvdeWeek::when($year, fn($q) => $q->whereYear('start_date', $year))
                               ->selectRaw('MONTH(start_date) as m')->distinct()->orderBy('m')->pluck('m');
        $tvde_weeks  = TvdeWeek::orderBy('start_date', 'desc')->get();

        return view('admin.vehicleProfitabilities.index', compact(
            'vehicle_items',
            'vehicle_item_id',
            'period',
            'groupBy',
            'year',
            'month',
            'tvde_years',
            'tvde_months',
            'tvde_weeks',
            'weeks',
            'rows',
            'groups',
            'totals'
        ));
    }

    /**
     * Cálculo de métricas para UMA semana.
     * - Deteta overlap de VehicleUsage (inclui end_date NULL).
     * - Blinda nulls em todas as leituras.
     */
    private function computeWeekMetrics(VehicleItem $vehicle_item, TvdeWeek $tvde_week): array
    {
        // Overlap do usage com a semana
        $vehicle_usage = VehicleUsage::with('driver.contract_vat')
            ->where('vehicle_item_id', $vehicle_item->id)
            ->whereDate('start_date', '<=', $tvde_week->end_date)
            ->where(function ($q) use ($tvde_week) {
                $q->whereDate('end_date', '>=', $tvde_week->start_date)
                  ->orWhereNull('end_date');
            })
            ->whereHas('driver') // garante que existe driver
            ->first();

        // Se não houver driver para a semana => linha a zeros
        if (!$vehicle_usage || !$vehicle_usage->driver) {
            return [
                'week'  => $tvde_week,
                'year'  => Carbon::parse($tvde_week->start_date)->year,
                'month' => Carbon::parse($tvde_week->start_date)->month,
                'driver' => null,
                'results' => (object)[],
                'receipt' => null,
                'adjustments' => 0,
                'rf' => 0,
                'iva' => 0,
                'fuel_transactions_vat' => 0,
                'vehicle_expenses_value' => 0,
                'vehicle_expenses_vat' => 0,
                'expense_reimbursements_value' => 0,
                'total_treasury' => 0,
                'total_taxes' => 0,
                'final_total' => 0,
            ];
        }

        $driver = $vehicle_usage->driver;

        // CurrentAccount
        $current = CurrentAccount::where([
            'tvde_week_id' => $tvde_week->id,
            'driver_id'    => $driver->id,
        ])->first();

        $results = $current ? json_decode($current->data) : (object)[];

        // IVA e Retenção (segundo o contrato do driver)
        $iva = round(($results->total ?? 0) * (($driver->contract_vat->iva ?? 0) / 100), 2);
        $rf  = round(($results->total ?? 0) * (($driver->contract_vat->rf  ?? 0) / 100), 2);

        // Ajustes da empresa
        $adjustments = 0;
        if (!empty($results->adjustments_array)) {
            foreach ($results->adjustments_array as $a) {
                if (!empty($a->company_expense)) {
                    $adjustments += ($a->type === 'deduct') ? -$a->amount : $a->amount;
                }
            }
        }

        // Recibo (transferido ao motorista)
        $receipt = Receipt::where([
            'tvde_week_id' => $tvde_week->id,
            'driver_id'    => $driver->id,
        ])->latest()->first();

        // IVA do combustível
        $fuel_transactions_vat = ($results->fuel_transactions ?? 0)
            ? (($results->fuel_transactions / 1.23) * 0.23)
            : 0;

        // Despesas da viatura nessa semana
        $vehicle_expenses = VehicleExpense::where('vehicle_item_id', $vehicle_item->id)
            ->whereDate('date', '>=', $tvde_week->start_date)
            ->whereDate('date', '<=', $tvde_week->end_date)
            ->get();

        $vehicle_expenses_value = 0;
        $vehicle_expenses_vat = 0;
        foreach ($vehicle_expenses as $ve) {
            if ($ve->vat !== null && $ve->vat > 0) {
                $vehicle_expenses_vat  += $ve->value * ($ve->vat / 100);
                $vehicle_expenses_value += $ve->value + ($ve->value * ($ve->vat / 100));
            } else {
                $vehicle_expenses_value += $ve->value;
            }
        }

        // Reembolsos da viatura
        $expense_reimbursements_value = ExpenseReimbursement::where('vehicle_item_id', $vehicle_item->id)
            ->whereDate('date', '>=', $tvde_week->start_date)
            ->whereDate('date', '<=', $tvde_week->end_date)
            ->sum('value');

        // Totais (mesma fórmula que já usavas)
        $total_treasury = ($results->total_net ?? 0)
            - ($results->car_track ?? 0)
            - ($results->fuel_transactions ?? 0)
            + ($adjustments ?? 0)
            - ($rf ?? 0)
            - ($receipt->amount_transferred ?? 0)
            - ($vehicle_expenses_value ?? 0)
            + ($expense_reimbursements_value ?? 0);

        $total_taxes = - ($results->vat_value ?? 0)
            + ($iva ?? 0)
            + ($fuel_transactions_vat ?? 0)
            + ($vehicle_expenses_vat ?? 0);

        $final_total = $total_treasury + $total_taxes;

        return [
            'week'  => $tvde_week,
            'year'  => Carbon::parse($tvde_week->start_date)->year,
            'month' => Carbon::parse($tvde_week->start_date)->month,

            'driver' => $driver,
            'results' => $results,
            'receipt' => $receipt,
            'adjustments' => $adjustments,
            'rf' => $rf,
            'iva' => $iva,
            'fuel_transactions_vat' => $fuel_transactions_vat,
            'vehicle_expenses_value' => $vehicle_expenses_value,
            'vehicle_expenses_vat' => $vehicle_expenses_vat,
            'expense_reimbursements_value' => $expense_reimbursements_value,

            'total_treasury' => $total_treasury,
            'total_taxes'    => $total_taxes,
            'final_total'    => $final_total,
        ];
    }

    public function setVehicleItemId($vehicle_item_id)
    {
        session()->put('vehicle_item_id', $vehicle_item_id);
        return redirect()->back();
    }
}
