<?php

namespace App\Services\Contracts;

use App\Models\AdminContract;
use App\Models\CarHire;
use App\Models\Driver;
use App\Models\Document;
use App\Models\VehicleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ContractTemplateRenderer
{
    public function render(string $content, Driver $driver, AdminContract $contract, ?VehicleItem $vehicle = null, array $overrides = []): array
    {
        $values = $this->values($driver, $contract, $vehicle, $overrides);
        $rendered = preg_replace_callback('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', function (array $match) use ($values) {
            return e((string) ($values[$match[1]] ?? ''));
        }, $content);

        return [
            'content' => $rendered,
            'data' => $values,
        ];
    }

    public function tags(): array
    {
        return [
            'contract.number' => 'Número do contrato',
            'contract.date' => 'Data de geração',
            'driver.name' => 'Nome do motorista',
            'driver.email' => 'E-mail do motorista',
            'driver.phone' => 'Telefone do motorista',
            'driver.vat' => 'NIF do motorista',
            'driver.citizen_card' => 'Documento de identificação',
            'driver.driver_license' => 'Carta de condução',
            'driver.tvde_certificate_number' => 'Certificado de motorista TVDE',
            'driver.address' => 'Morada do motorista',
            'driver.zip' => 'Código postal do motorista',
            'driver.city' => 'Localidade do motorista',
            'driver.full_address' => 'Morada completa do motorista',
            'driver.iban' => 'IBAN do motorista',
            'driver.iban_clause' => 'Cláusula de IBAN, quando preenchido',
            'driver.weekly_cedencia' => 'Valor semanal da cedência',
            'driver.commission_percent' => 'Percentagem de comissão',
            'financial.weekly_clause' => 'Cláusula do valor semanal',
            'financial.commission_clause' => 'Cláusula da comissão',
            'documents.citizen_card_expires_at' => 'Validade do documento de identificação',
            'documents.tvde_certificate_expires_at' => 'Validade do certificado TVDE',
            'documents.driving_license_expires_at' => 'Validade da carta de condução',
            'documents.passport_expires_at' => 'Validade do passaporte',
            'documents.residence_permit_expires_at' => 'Validade da autorização de residência',
            'company.name' => 'Nome da empresa',
            'company.vat' => 'NIF da empresa',
            'company.tvde_license' => 'Licença de operador TVDE',
            'company.address' => 'Morada da empresa',
            'company.zip' => 'Código postal da empresa',
            'company.location' => 'Localidade da empresa',
            'company.full_address' => 'Morada completa da empresa',
            'company.email' => 'E-mail da empresa',
            'vehicle.brand' => 'Marca da viatura',
            'vehicle.model' => 'Modelo da viatura',
            'vehicle.license_plate' => 'Matrícula da viatura',
            'vehicle.year' => 'Ano da viatura',
        ];
    }

    public function values(Driver $driver, AdminContract $contract, ?VehicleItem $vehicle = null, array $overrides = []): array
    {
        $company = $driver->company;
        $documents = $driver->exists && Schema::hasTable('documents')
            ? Document::where('driver_id', $driver->id)->latest('id')->first()
            : null;
        $weeklyCedencia = array_key_exists('weekly_cedencia', $overrides)
            ? $this->nullableFloat($overrides['weekly_cedencia'])
            : $this->weeklyCedencia($driver, $contract);
        $contractVat = $driver->relationLoaded('contract_vat')
            ? $driver->getRelation('contract_vat')
            : ($driver->exists && Schema::hasTable('contract_vats') ? $driver->contract_vat : null);
        $commissionPercent = array_key_exists('commission_percent', $overrides)
            ? $this->nullableFloat($overrides['commission_percent'])
            : $this->nullableFloat(optional($contractVat)->percent);
        $documentOverrides = $overrides['document_expiries'] ?? [];
        $vehicleYear = $overrides['vehicle_year'] ?? optional($vehicle)->year;

        return [
            'contract.number' => $contract->number,
            'contract.date' => Carbon::parse($contract->generated_at ?: now())->format('d/m/Y'),
            'driver.name' => $driver->name,
            'driver.email' => $driver->effective_email,
            'driver.phone' => $driver->phone,
            'driver.vat' => $driver->driver_vat,
            'driver.citizen_card' => $driver->citizen_card,
            'driver.driver_license' => $driver->driver_license,
            'driver.tvde_certificate_number' => $driver->tvde_certificate_number,
            'driver.address' => $driver->address,
            'driver.zip' => $driver->zip,
            'driver.city' => $driver->city,
            'driver.full_address' => collect([$driver->address, $driver->zip, $driver->city])->filter()->implode(', '),
            'driver.iban' => $driver->iban,
            'driver.iban_clause' => filled($driver->iban) ? ', com IBAN '.$driver->iban : '',
            'driver.weekly_cedencia' => $weeklyCedencia !== null
                ? number_format($weeklyCedencia, 2, ',', '.').' €'
                : 'Não indicado',
            'driver.commission_percent' => $commissionPercent !== null
                ? number_format($commissionPercent, 2, ',', '.').' %'
                : 'Não indicada',
            'financial.weekly_clause' => $weeklyCedencia !== null && $weeklyCedencia > 0
                ? 'Pela cedência da viatura, o MOTORISTA pagará à CONTRATANTE '.number_format($weeklyCedencia, 2, ',', '.').' € por semana, a deduzir da faturação semanal, acrescido de IVA à taxa legal aplicável quando devido.'
                : 'Não é devido um valor semanal fixo pela cedência da viatura, aplicando-se a comissão percentual prevista neste contrato.',
            'financial.commission_clause' => $commissionPercent !== null && $commissionPercent > 0
                ? 'Será deduzida uma comissão de gestão e cedência de viatura de '.number_format($commissionPercent, 2, ',', '.').' %, calculada sobre a faturação do MOTORISTA sem IVA, acrescida de IVA à taxa legal aplicável quando devido.'
                : 'Não será deduzida uma comissão percentual adicional.',
            'documents.citizen_card_expires_at' => $this->dateOrNotProvided($documentOverrides['citizen_card_expires_at'] ?? optional($documents)->citizen_card_expires_at),
            'documents.tvde_certificate_expires_at' => $this->dateOrNotProvided($documentOverrides['tvde_driver_certificate_expires_at'] ?? optional($documents)->tvde_driver_certificate_expires_at),
            'documents.driving_license_expires_at' => $this->dateOrNotProvided($documentOverrides['driving_license_expires_at'] ?? optional($documents)->driving_license_expires_at),
            'documents.passport_expires_at' => $this->dateOrNotProvided($documentOverrides['passport_expires_at'] ?? optional($documents)->passport_expires_at),
            'documents.residence_permit_expires_at' => $this->dateOrNotProvided($documentOverrides['residence_permit_expires_at'] ?? optional($documents)->residence_permit_expires_at),
            'company.name' => optional($company)->name,
            'company.vat' => optional($company)->vat,
            'company.tvde_license' => optional($company)->tvde_license,
            'company.address' => optional($company)->address,
            'company.zip' => optional($company)->zip,
            'company.location' => optional($company)->location,
            'company.full_address' => collect([optional($company)->address, optional($company)->zip, optional($company)->location])->filter()->implode(', '),
            'company.email' => optional($company)->email,
            'vehicle.brand' => optional(optional($vehicle)->vehicle_brand)->name ?: $driver->brand,
            'vehicle.model' => optional(optional($vehicle)->vehicle_model)->name ?: $driver->model,
            'vehicle.license_plate' => optional($vehicle)->license_plate ?: $driver->license_plate,
            'vehicle.year' => $vehicleYear,
        ];
    }

    private function dateOrNotProvided($value): string
    {
        return filled($value) ? (string) $value : 'Não indicado';
    }

    private function weeklyCedencia(Driver $driver, AdminContract $contract): ?float
    {
        if (! $driver->exists || ! Schema::hasTable('car_hires')) {
            return null;
        }

        $date = Carbon::parse($contract->generated_at ?: now())->toDateString();
        $carHire = CarHire::query()
            ->where('driver_id', $driver->id)
            ->where(function ($query) use ($date) {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $carHire && $carHire->amount !== null ? (float) $carHire->amount : null;
    }

    private function nullableFloat($value): ?float
    {
        return filled($value) ? (float) str_replace(',', '.', (string) $value) : null;
    }
}
