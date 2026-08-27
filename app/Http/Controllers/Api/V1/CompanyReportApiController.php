<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\Reports;
use App\Models\Company;
use App\Models\Driver;
use App\Models\TvdeWeek;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompanyReportApiController extends Controller
{
    use Reports;

    public function weekly(Request $request)
    {
        if (! $this->canViewCompanyReports($request)) {
            return response()->json(['error' => '403 Forbidden'], 403);
        }

        $company = $this->resolveCompany($request);

        if (! $company) {
            return response()->json([
                'error' => 'Empresa nao encontrada para o utilizador autenticado.',
            ], 404);
        }

        [$week, $requestedDate] = $this->resolveWeek($request->query('date'));

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
            ], 404);
        }

        $results = $this->getWeekReport($company->id, $week->id);

        return response()->json([
            'company' => [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
            ],
            'week' => [
                'id' => (int) $week->id,
                'number' => $week->display_number ? (int) $week->display_number : null,
                'start_date' => Carbon::parse($week->getRawOriginal('start_date'))->format('d-m-Y'),
                'end_date' => Carbon::parse($week->getRawOriginal('end_date'))->format('d-m-Y'),
                'requested_date' => $requestedDate,
            ],
            'data' => [
                'drivers' => collect($results['drivers'])
                    ->filter(fn ($driver) => !empty($driver->earnings))
                    ->map(function (Driver $driver) {
                        return [
                            'id' => (int) $driver->id,
                            'name' => (string) $driver->name,
                            'license_plate' => $driver->license_plate,
                            'weekly_km' => (float) ($driver->weekly_km ?? 0),
                            'earnings_per_km' => (float) ($driver->earnings_per_km ?? 0),
                            'uber_net' => (float) data_get($driver->earnings, 'uber.uber_net', 0),
                            'bolt_net' => (float) data_get($driver->earnings, 'bolt.bolt_net', 0),
                            'tips_total' => (float) data_get($driver->earnings, 'tips_total', 0),
                            'vat_value' => (float) data_get($driver->earnings, 'iva_value', 0),
                            'fuel' => (float) ($driver->fuel ?? 0),
                            'adjustments' => (float) ($driver->adjustments ?? 0),
                            'general_adjustments' => (float) data_get($driver->earnings, 'general_adjustments', $driver->adjustments ?? 0),
                            'abatimento_aluguer' => (float) data_get($driver->earnings, 'abatimento_aluguer', 0),
                            'diferenca_faturacao_minima' => (float) data_get($driver->earnings, 'diferenca_faturacao_minima', 0),
                            'caucao_recebida' => (float) data_get($driver->earnings, 'caucao_recebida', 0),
                            'caucao_devolvida' => (float) data_get($driver->earnings, 'caucao_devolvida', 0),
                            'via_verde' => (float) data_get($driver->earnings, 'car_track', 0),
                            'percent_value' => (float) data_get($driver->earnings, 'percent_value', 0),
                            'car_hire' => (float) data_get($driver->earnings, 'car_hire', 0),
                            'total' => (float) ($driver->total ?? 0),
                            'last_balance' => (float) ($driver->last_balance ?? 0),
                            'new_balance' => (float) ($driver->new_balance ?? 0),
                            'manual_status' => $driver->balance_manual_status,
                            'manual_status_label' => $driver->balance_manual_status_label,
                            'receipt_check' => [
                                'status' => data_get($driver->receipt_check, 'status'),
                                'platform_net_total' => (float) data_get($driver->receipt_check, 'platform_net_total', 0),
                                'received_in_account' => (($receivedInAccount = data_get($driver->receipt_check, 'received_in_account')) !== null)
                                    ? (float) $receivedInAccount
                                    : null,
                                'difference' => (($difference = data_get($driver->receipt_check, 'difference')) !== null)
                                    ? (float) $difference
                                    : null,
                                'receipt_id' => data_get($driver->receipt_check, 'receipt_id'),
                                'amount_transferred' => (($amountTransferred = data_get($driver->receipt_check, 'amount_transferred')) !== null)
                                    ? (float) $amountTransferred
                                    : null,
                            ],
                            'validated' => (bool) ($driver->current_account ?? false),
                        ];
                    })
                    ->sortByDesc('total')
                    ->values(),
                'totals' => [
                    'net_uber' => (float) ($results['totals']['net_uber'] ?? 0),
                    'net_bolt' => (float) ($results['totals']['net_bolt'] ?? 0),
                    'total_weekly_km' => (float) ($results['totals']['total_weekly_km'] ?? 0),
                    'total_earnings_per_km' => (float) ($results['totals']['total_earnings_per_km'] ?? 0),
                    'total_drivers' => (float) ($results['totals']['total_drivers'] ?? 0),
                    'tips_total' => (float) ($results['totals']['tips_total'] ?? 0),
                    'total_iva_value' => (float) ($results['totals']['total_iva_value'] ?? 0),
                    'total_fuel_transactions' => (float) ($results['totals']['total_fuel_transactions'] ?? 0),
                    'total_adjustments' => (float) ($results['totals']['total_adjustments'] ?? 0),
                    'total_general_adjustments' => (float) ($results['totals']['total_general_adjustments'] ?? 0),
                    'total_rent_discounts' => (float) ($results['totals']['total_rent_discounts'] ?? 0),
                    'total_minimum_billing_difference' => (float) ($results['totals']['total_minimum_billing_difference'] ?? 0),
                    'total_caution_received' => (float) ($results['totals']['total_caution_received'] ?? 0),
                    'total_caution_returned' => (float) ($results['totals']['total_caution_returned'] ?? 0),
                    'total_car_track' => (float) ($results['totals']['total_car_track'] ?? 0),
                    'total_percent_value' => (float) ($results['totals']['total_percent_value'] ?? 0),
                    'total_car_hire' => (float) ($results['totals']['total_car_hire'] ?? 0),
                    'receipt_check_match_count' => (int) ($results['totals']['receipt_check_match_count'] ?? 0),
                    'receipt_check_mismatch_count' => (int) ($results['totals']['receipt_check_mismatch_count'] ?? 0),
                    'receipt_check_missing_count' => (int) ($results['totals']['receipt_check_missing_count'] ?? 0),
                    'receipt_check_difference_total' => (float) ($results['totals']['receipt_check_difference_total'] ?? 0),
                ],
            ],
        ]);
    }

    protected function canViewCompanyReports(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->hasRole('Admin');
    }

    protected function resolveCompany(Request $request): ?Company
    {
        $requestedCompanyId = $request->integer('company_id');
        if ($requestedCompanyId) {
            return Company::find($requestedCompanyId);
        }

        $driver = Driver::where('user_id', $request->user()->id)->first();
        if ($driver?->company_id) {
            return Company::find($driver->company_id);
        }

        return Company::where('main', true)->first() ?? Company::orderBy('id')->first();
    }

    protected function resolveWeek(?string $date): array
    {
        if ($date) {
            try {
                $parsedDate = Carbon::createFromFormat('d-m-Y', $date)->startOfDay();
                $week = TvdeWeek::whereDate('start_date', '<=', $parsedDate)
                    ->whereDate('end_date', '>=', $parsedDate)
                    ->first();

                if ($week) {
                    return [$week, $date];
                }
            } catch (\Throwable $exception) {
            }
        }

        $week = TvdeWeek::orderByDesc('start_date')->first();

        return [$week, $date];
    }
}
