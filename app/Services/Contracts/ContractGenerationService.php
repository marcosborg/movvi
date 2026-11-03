<?php

namespace App\Services\Contracts;

use App\Models\AdminContract;
use App\Models\ContractTemplate;
use App\Models\Driver;
use App\Models\VehicleItem;
use Illuminate\Support\Facades\DB;

class ContractGenerationService
{
    public function __construct(private ContractTemplateRenderer $renderer)
    {
    }

    public function activeTemplate(): ContractTemplate
    {
        $active = ContractTemplate::where('is_active', true)->latest('version')->first();

        if ($active) {
            return $active;
        }

        return ContractTemplate::create([
            'name' => DefaultContractTemplate::NAME,
            'version' => 1,
            'content' => DefaultContractTemplate::content(),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
    }

    public function generate(Driver $driver, ?ContractTemplate $template = null): AdminContract
    {
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

            $driver->loadMissing('company');
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
