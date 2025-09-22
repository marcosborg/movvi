<?php

namespace App\Http\Requests;

use App\Models\CompanyData;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyCompanyDataRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('company_data_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:company_datas,id',
        ];
    }
}
