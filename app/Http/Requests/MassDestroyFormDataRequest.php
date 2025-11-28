<?php

namespace App\Http\Requests;

use App\Models\FormData;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyFormDataRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('form_data_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:form_datas,id',
        ];
    }
}
