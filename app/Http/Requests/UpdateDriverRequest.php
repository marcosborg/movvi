<?php

namespace App\Http\Requests;

use App\Models\Driver;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('driver_edit');
    }

    public function rules()
    {
        $driver = $this->route('driver');
        $driverId = $driver instanceof Driver ? $driver->id : $driver;
        $uberUuidRules = [
            'string',
            'nullable',
        ];

        if (filled($this->input('uber_uuid'))) {
            $currentUberUuid = $driver instanceof Driver ? $driver->uber_uuid : null;

            if ((string) $this->input('uber_uuid') !== (string) $currentUberUuid) {
                $uberUuidRules[] = Rule::unique('drivers', 'uber_uuid')
                    ->ignore($driverId)
                    ->whereNull('deleted_at');
            }
        }

        return [
            'code' => [
                'string',
                'required',
            ],
            'name' => [
                'string',
                'required',
            ],
            'cards.*' => [
                'integer',
            ],
            'cards' => [
                'array',
            ],
            'contract_vat_id' => [
                'required',
                'integer',
            ],
            'start_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'end_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'reason' => [
                'string',
                'nullable',
            ],
            'phone' => [
                'string',
                'nullable',
            ],
            'payment_vat' => [
                'string',
                'nullable',
            ],
            'citizen_card' => [
                'string',
                'nullable',
            ],
            'iban' => [
                'string',
                'nullable',
            ],
            'address' => [
                'string',
                'nullable',
            ],
            'zip' => [
                'string',
                'nullable',
            ],
            'city' => [
                'string',
                'nullable',
            ],
            'state_id' => [
                'required',
                'integer',
            ],
            'driver_license' => [
                'string',
                'nullable',
            ],
            'driver_vat' => [
                'string',
                'nullable',
            ],
            'uber_uuid' => [
                ...$uberUuidRules,
            ],
            'bolt_name' => [
                'string',
                'nullable',
            ],
            'bolt_individual_id' => [
                'string',
                'nullable',
            ],
            'license_plate' => [
                'string',
                'nullable',
            ],
            'brand' => [
                'string',
                'nullable',
            ],
            'model' => [
                'string',
                'nullable',
            ],
        ];
    }
}
