<?php

namespace App\Http\Requests;

use App\Models\AdminContract;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAdminContractRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('admin_contract_create');
    }

    public function rules()
    {
        return [
            'number' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'driver_id' => [
                'required',
                'integer',
                'exists:drivers,id',
            ],
            'contract_template_id' => [
                'nullable',
                'integer',
                'exists:contract_templates,id',
            ],
            'signed_at' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'weekly_cedencia' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'commission_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'identification_type' => [
                'required',
                'in:CC,BI',
            ],
            'vehicle_year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (now()->year + 1),
            ],
            'document_expiries.citizen_card_expires_at' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'document_expiries.tvde_driver_certificate_expires_at' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'document_expiries.driving_license_expires_at' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'document_expiries.passport_expires_at' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'document_expiries.residence_permit_expires_at' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
        ];
    }
}
