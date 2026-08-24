<?php

namespace App\Http\Requests;

use App\Models\VehicleItem;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateVehicleItemRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_item_edit');
    }

    public function rules()
    {
        return [
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'vehicle_brand_id' => [
                'required',
                'integer',
            ],
            'vehicle_model_id' => [
                'required',
                'integer',
            ],
            'year' => [
                'string',
                'required',
            ],
            'license_plate' => [
                'string',
                'required',
            ],
            'documents' => [
                'array',
            ],
            'acquisition_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'acquisition_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'sale_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'sale_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'fuel_card_id' => [
                'nullable',
                'integer',
                'exists:cards,id',
            ],
            'suspended' => [
                'boolean',
            ],
            'is_service_vehicle' => ['boolean'],
        ];
    }
}
