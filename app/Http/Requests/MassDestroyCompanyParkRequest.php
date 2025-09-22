<?php

namespace App\Http\Requests;

use App\Models\CompanyPark;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyCompanyParkRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('company_park_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:company_parks,id',
        ];
    }
}
