<?php

namespace App\Http\Requests;

use App\Models\ContractVat;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateContractVatRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('contract_vat_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'percent' => [
                'numeric',
                'required',
            ],
            'rf' => [
                'numeric',
                'required',
            ],
            'iva' => [
                'numeric',
                'required',
            ],
        ];
    }
}
