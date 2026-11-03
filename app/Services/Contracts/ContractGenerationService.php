<?php

namespace App\Services\Contracts;

use App\Models\AdminContract;
use App\Models\ContractTemplate;
use App\Models\Driver;
use App\Models\VehicleItem;
use Illuminate\Support\Facades\DB;
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

    public function generate(Driver $driver, ?ContractTemplate $template = null): AdminContract
    {
        $driver->loadMissing('company');

        if (blank(optional($driver->company)->tvde_license)) {
            throw ValidationException::withMessages([
                'company.tvde_license' => 'Preencha a licença de operador TVDE na ficha da empresa antes de gerar o contrato.',
            ]);
        }

        return DB::transaction(function () use ($driver, $template) {
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

            $vehicle = $this->activeVehicle($driver);
            $result = $this->renderer->render($template->content, $driver, $contract, $vehicle);

            $contract->rendered_content = $result['content'];
            $contract->data_snapshot = $result['data'];
            $contract->save();

            return $contract;
        });
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
