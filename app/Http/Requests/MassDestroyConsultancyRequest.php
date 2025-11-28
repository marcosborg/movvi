<?php

namespace App\Http\Requests;

use App\Models\Consultancy;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyConsultancyRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('consultancy_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:consultancies,id',
        ];
    }
}