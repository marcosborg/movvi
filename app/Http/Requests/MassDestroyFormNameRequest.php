<?php

namespace App\Http\Requests;

use App\Models\FormName;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyFormNameRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('form_name_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:form_names,id',
        ];
    }
}
