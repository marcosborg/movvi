<?php

namespace App\Http\Requests;

use App\Models\CompanyData;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCompanyDataRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('company_data_create');
    }

    public function rules()
    {
        return [];
    }
}
