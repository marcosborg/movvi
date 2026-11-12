<?php

namespace App\Services\Contracts;

use App\Models\AdminContract;
use App\Models\ContractTemplate;
use App\Models\Driver;
use App\Models\VehicleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ContractGenerationService
{
    public function __construct(private ContractTemplateRenderer $renderer)
    {
    }

    public function activeTemplate(): ContractTemplate
    {
        $active = ContractTemplate::where('is_active', true)->latest('version')->first();

        if ($active && ($active->name !== DefaultContractTemplate::NAME || $active->version >= DefaultContractTemplate::VERSION)) {
            return $active;
        }

        return DB::transaction(function () {
            ContractTemplate::where('is_active', true)->update(['is_active' => false]);

            $template = ContractTemplate::firstOrCreate(
                [
                    'name' => DefaultContractTemplate::NAME,
                    'version' => DefaultContractTemplate::VERSION,
                ],
                [
                    'content' => DefaultContractTemplate::content(),
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]
            );

            if (! $template->is_active) {
                $template->update(['is_active' => true]);
            }

            return $template;
        });
    }

    public function generate(Driver $driver, ?ContractTemplate $template = null, array $overrides = []): AdminContract
    {
        $driver->loadMissing('company');
        if (Schema::hasTable('contract_vats')) {
            $driver->loadMissing('contract_vat');
        }

        $missing = collect([
            'driver.name' => ['value' => $driver->name, 'label' => 'nome do motorista'],
            'driver.email' => ['value' => $driver->effective_email, 'label' => 'e-mail'],
            'driver.phone' => ['value' => $driver->phone, 'label' => 'telefone'],
            'driver.driver_vat' => ['value' => $driver->driver_vat, 'label' => 'NIF'],
            'driver.citizen_card' => ['value' => $driver->citizen_card, 'label' => 'documento de identificação'],
            'driver.driver_license' => ['value' => $driver->driver_license, 'label' => 'carta de condução'],
            'driver.tvde_certificate_number' => ['value' => $driver->tvde_certificate_number, 'label' => 'certificado de motorista TVDE'],
            'driver.address' => ['value' => $driver->address, 'label' => 'morada'],
            'driver.zip' => ['value' => $driver->zip, 'label' => 'código postal'],
            'driver.city' => ['value' => $driver->city, 'label' => 'localidade'],
            'company.tvde_license' => ['value' => optional($driver->company)->tvde_license, 'label' => 'licença de operador TVDE da empresa'],
        ])->filter(fn (array $field) => blank($field['value']));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'contract_data' => 'Não foi possível gerar o contrato. Preencha primeiro: ' . $missing->pluck('label')->implode(', ') . '.',
            ]);
        }

        $vehicle = $this->activeVehicle($driver);
        if ($overrides !== []) {
            $previewContract = new AdminContract(['generated_at' => now()]);
            $values = $this->renderer->values($driver, $previewContract, $vehicle, $overrides);
            $missingContractData = collect([
                'documents.citizen_card_expires_at' => 'validade do documento de identificação',
                'documents.tvde_certificate_expires_at' => 'validade do certificado TVDE',
                'documents.driving_license_expires_at' => 'validade da carta de condução',
                'vehicle.year' => 'ano da viatura',
            ])->filter(fn (string $label, string $key) => blank($values[$key] ?? null) || ($values[$key] ?? null) === 'Não indicado');

            $hasWeeklyValue = ($values['driver.weekly_cedencia'] ?? 'Não indicado') !== 'Não indicado';
            $hasCommission = ($values['driver.commission_percent'] ?? 'Não indicada') !== 'Não indicada';
            if (! $hasWeeklyValue && ! $hasCommission) {
                $missingContractData->put('financial.terms', 'valor semanal da cedência ou percentagem de comissão');
            }

            if ($missingContractData->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'contract_data' => 'Não foi possível gerar o contrato. Preencha primeiro: '.$missingContractData->implode(', ').'.',
                ]);
            }
        }

        return DB::transaction(function () use ($driver, $template, $overrides, $vehicle) {
            $template ??= $this->activeTemplate();
            $number = ((int) AdminContract::withTrashed()->lockForUpdate()->max('number')) + 1;
            $generatedAt = now();

            $contract = new AdminContract([
                'number' => $number,
                'driver_id' => $driver->id,
                'contract_template_id' => $template->id,
                'template_version' => $template->version,
                'source_content' => $template->content,
                'generated_at' => $generatedAt,
                'generated_by' => auth()->id(),
            ]);

            $result = $this->renderer->render($template->content, $driver, $contract, $vehicle, $overrides);

            $contract->rendered_content = $result['content'];
            $contract->data_snapshot = $result['data'];
            $contract->save();

            return $contract;
        });
    }

    public function formDefaults(Driver $driver): array
    {
        $driver->loadMissing('company');
        if (Schema::hasTable('contract_vats')) {
            $driver->loadMissing('contract_vat');
        }
        $contract = new AdminContract(['generated_at' => now()]);
        $vehicle = $this->activeVehicle($driver);
        $values = $this->renderer->values($driver, $contract, $vehicle);

        return [
            'weekly_cedencia' => $values['driver.weekly_cedencia'] === 'Não indicado'
                ? null
                : str_replace(['.', ',', ' €'], ['', '.', ''], $values['driver.weekly_cedencia']),
            'commission_percent' => $values['driver.commission_percent'] === 'Não indicada'
                ? null
                : str_replace(['.', ',', ' %'], ['', '.', ''], $values['driver.commission_percent']),
            'vehicle_year' => $values['vehicle.year'],
            'document_expiries' => [
                'citizen_card_expires_at' => $this->editableDate($values['documents.citizen_card_expires_at']),
                'tvde_driver_certificate_expires_at' => $this->editableDate($values['documents.tvde_certificate_expires_at']),
                'driving_license_expires_at' => $this->editableDate($values['documents.driving_license_expires_at']),
                'passport_expires_at' => $this->editableDate($values['documents.passport_expires_at']),
                'residence_permit_expires_at' => $this->editableDate($values['documents.residence_permit_expires_at']),
            ],
        ];
    }

    private function editableDate($value): ?string
    {
        return $value === 'Não indicado' ? null : (string) $value;
    }

    private function activeVehicle(Driver $driver): ?VehicleItem
    {
        $usage = $driver->activeVehicleUsages()
            ->with(['vehicle_item.vehicle_brand', 'vehicle_item.vehicle_model'])
            ->latest('start_date')
            ->first();

        if ($usage && $usage->vehicle_item) {
            return $usage->vehicle_item;
        }

        return VehicleItem::with(['vehicle_brand', 'vehicle_model'])
            ->where('driver_id', $driver->id)
            ->latest('id')
            ->first();
    }
}
