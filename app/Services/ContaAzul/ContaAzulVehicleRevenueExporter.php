<?php

namespace App\Services\ContaAzul;

use App\Models\Company;
use App\Models\ContaAzulVehicleRevenueExport;
use App\Models\TvdeWeek;
use App\Services\VehicleProfitabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ContaAzulVehicleRevenueExporter
{
    public function __construct(
        protected ContaAzulClient $client
    ) {
    }

    public function exportWeek(Company $company, TvdeWeek $week, int $userId, ?array $selectedVehicleIds = null): array
    {
        $connection = $this->client->requireConnectionForCompany($company);

        if (!filled($connection->receivable_contact_id) || !filled($connection->receivable_financial_account_id)) {
            throw new \RuntimeException('Configure o contacto e a conta financeira da Conta Azul antes de exportar receitas da viatura.');
        }

        $snapshot = VehicleProfitabilityService::makeWeek($week->id, $company->id);

        $rows = collect($snapshot['vehicles'] ?? [])
            ->filter(fn (array $vehicle) => (float) ($vehicle['total_revenue'] ?? 0) > 0)
            ->when(is_array($selectedVehicleIds) && ! empty($selectedVehicleIds), function (Collection $collection) use ($selectedVehicleIds) {
                $selectedIds = collect($selectedVehicleIds)->map(fn ($id) => (int) $id)->filter()->values()->all();

                return $collection->whereIn('id', $selectedIds);
            })
            ->values();

        if ($rows->isEmpty()) {
            return [
                'exported' => 0,
                'skipped' => 0,
                'errors' => 0,
                'items' => [],
            ];
        }

        $exported = 0;
        $skipped = 0;
        $errors = 0;
        $items = [];

        foreach ($rows as $row) {
            $vehicleId = (int) $row['id'];

            if (ContaAzulVehicleRevenueExport::where([
                'company_id' => $company->id,
                'tvde_week_id' => $week->id,
                'vehicle_item_id' => $vehicleId,
                'status' => ContaAzulVehicleRevenueExport::STATUS_EXPORTED,
            ])->exists()) {
                $skipped++;
                $items[] = [
                    'vehicle_item_id' => $vehicleId,
                    'license_plate' => $row['license_plate'],
                    'status' => 'skipped',
                    'message' => 'Ja exportado anteriormente.',
                ];
                continue;
            }

            $payload = $this->buildReceivablePayload($connection, $week, $row);

            try {
                $event = $this->client->createReceivableEvent($company, $payload);
                $eventId = (string) ($event['id'] ?? $event['uuid'] ?? '');

                $installment = null;
                $acquittance = null;

                if ($eventId !== '') {
                    $installments = $this->client->listEventInstallments($company, $eventId);
                    $installment = collect($installments)->first();

                    if (!empty($installment['id'])) {
                        $acquittance = $this->client->createAcquittance($company, (string) $installment['id'], [
                            'data_pagamento' => Carbon::parse($week->end_date)->toDateString(),
                            'valor' => round((float) $row['total_revenue'], 2),
                            'conta_financeira' => $connection->receivable_financial_account_id,
                            'metodo_pagamento' => $connection->receivable_payment_method ?: 'TRANSFERENCIA_BANCARIA',
                            'observacao' => sprintf('Receita da viatura %s - semana %s a %s', $row['license_plate'], $week->start_date, $week->end_date),
                        ]);
                    }
                }

                ContaAzulVehicleRevenueExport::updateOrCreate([
                    'company_id' => $company->id,
                    'tvde_week_id' => $week->id,
                    'vehicle_item_id' => $vehicleId,
                ], [
                    'company_id' => $company->id,
                    'tvde_week_id' => $week->id,
                    'vehicle_item_id' => $vehicleId,
                    'license_plate' => $row['license_plate'],
                    'amount' => round((float) $row['total_revenue'], 2),
                    'description' => $payload['descricao'],
                    'status' => ContaAzulVehicleRevenueExport::STATUS_EXPORTED,
                    'conta_azul_event_id' => $eventId ?: null,
                    'conta_azul_installment_id' => $installment['id'] ?? null,
                    'conta_azul_acquittance_id' => $acquittance['id'] ?? null,
                    'request_payload' => $payload,
                    'event_payload' => $event,
                    'installment_payload' => $installment,
                    'acquittance_payload' => $acquittance,
                    'exported_at' => now(),
                    'exported_by' => $userId,
                ]);

                $exported++;
                $items[] = [
                    'vehicle_item_id' => $vehicleId,
                    'license_plate' => $row['license_plate'],
                    'status' => 'exported',
                    'amount' => round((float) $row['total_revenue'], 2),
                ];
            } catch (\Throwable $exception) {
                ContaAzulVehicleRevenueExport::updateOrCreate([
                    'company_id' => $company->id,
                    'tvde_week_id' => $week->id,
                    'vehicle_item_id' => $vehicleId,
                ], [
                    'company_id' => $company->id,
                    'tvde_week_id' => $week->id,
                    'vehicle_item_id' => $vehicleId,
                    'license_plate' => $row['license_plate'],
                    'amount' => round((float) $row['total_revenue'], 2),
                    'description' => $payload['descricao'],
                    'status' => ContaAzulVehicleRevenueExport::STATUS_ERROR,
                    'request_payload' => $payload,
                    'error_message' => $exception->getMessage(),
                    'exported_at' => now(),
                    'exported_by' => $userId,
                ]);

                $items[] = [
                    'vehicle_item_id' => $vehicleId,
                    'license_plate' => $row['license_plate'],
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                ];
                $errors++;
            }
        }

        return [
            'exported' => $exported,
            'skipped' => $skipped,
            'errors' => $errors,
            'items' => $items,
        ];
    }

    protected function buildReceivablePayload($connection, TvdeWeek $week, array $row): array
    {
        $amount = round((float) $row['total_revenue'], 2);

        return [
            'descricao' => sprintf('Receita %s [%s]', $row['license_plate'], $week->id),
            'contato' => $connection->receivable_contact_id,
            'data_competencia' => Carbon::parse($week->end_date)->toDateString(),
            'conta_financeira' => $connection->receivable_financial_account_id,
            'condicao_pagamento' => [
                'parcelas' => [
                    [
                        'data_vencimento' => Carbon::parse($week->end_date)->toDateString(),
                        'detalhe_valor' => [
                            'valor_bruto' => $amount,
                            'valor_liquido' => $amount,
                        ],
                    ],
                ],
            ],
            'observacao' => sprintf(
                'Receita por matrícula %s referente à semana %s a %s',
                $row['license_plate'],
                $week->start_date,
                $week->end_date
            ),
        ];
    }
}
