<?php

namespace App\Http\Requests;

use App\Models\Document;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('document_create');
    }

    public function rules()
    {
        return [
            'driver_id' => [
                'required',
                'integer',
            ],
            'citizen_card' => [
                'array',
            ],
            'tvde_driver_certificate' => [
                'array',
            ],
            'criminal_record' => [
                'array',
            ],
            'driving_license' => [
                'array',
            ],
            'iban' => [
                'array',
            ],
            'address' => [
                'array',
            ],
            'citizen_card_expires_at' => ['nullable', 'date_format:' . config('panel.date_format')],
            'tvde_driver_certificate_expires_at' => ['nullable', 'date_format:' . config('panel.date_format')],
            'criminal_record_expires_at' => ['nullable', 'date_format:' . config('panel.date_format')],
            'driving_license_expires_at' => ['nullable', 'date_format:' . config('panel.date_format')],
        ];
    }
}
