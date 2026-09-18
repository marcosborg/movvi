<?php

namespace App\Http\Requests;

use App\Models\Driver;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('driver_create');
    }

    public function rules()
    {
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
                'required',
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
                'required',
            ],
            'email' => [
                'string',
                'email',
                'required',
            ],
            'uber_uuid' => [
                'string',
                'nullable',
                Rule::unique('drivers', 'uber_uuid')->whereNull('deleted_at'),
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
            'pays_fuel' => [
                'required',
                'boolean',
            ],
            'weekly_km_limit' => ['nullable', 'numeric', 'min:0'],
            'excess_km_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
