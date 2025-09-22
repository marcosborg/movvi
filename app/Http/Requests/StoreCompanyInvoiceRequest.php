<?php

namespace App\Http\Requests;

use App\Models\CompanyInvoice;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCompanyInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('company_invoice_create');
    }

    public function rules()
    {
        return [
            'company_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'invoice' => [
                'array',
            ],
        ];
    }
}
