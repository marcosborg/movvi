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
                'number' => $week->number ? (int) $week->number : null,
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
                            'via_verde' => (float) data_get($driver->earnings, 'car_track', 0),
                            'percent_value' => (float) data_get($driver->earnings, 'percent_value', 0),
                            'car_hire' => (float) data_get($driver->earnings, 'car_hire', 0),
                            'total' => (float) ($driver->total ?? 0),
                            'last_balance' => (float) ($driver->last_balance ?? 0),
                            'new_balance' => (float) ($driver->new_balance ?? 0),
                            'validated' => (bool) ($driver->current_account ?? false),
                        ];
                    })
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
                    'total_car_track' => (float) ($results['totals']['total_car_track'] ?? 0),
                    'total_percent_value' => (float) ($results['totals']['total_percent_value'] ?? 0),
                    'total_car_hire' => (float) ($results['totals']['total_car_hire'] ?? 0),
                ],
            ],
        ]);
    }

    protected function canViewCompanyReports(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole('Admin') || $user->hasRole('Gestor'));
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
