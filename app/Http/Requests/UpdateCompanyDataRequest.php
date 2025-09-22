<?php

namespace App\Http\Requests;

use App\Models\CompanyData;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateCompanyDataRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('company_data_edit');
    }

    public function rules()
    {
        return [];
    }
}
