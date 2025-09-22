<?php

namespace App\Http\Requests;

use App\Models\CompanyPark;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCompanyParkRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('company_park_create');
    }

    public function rules()
    {
        return [
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'company_id' => [
                'required',
                'integer',
            ],
            'value' => [
                'required',
            ],
        ];
    }
}
