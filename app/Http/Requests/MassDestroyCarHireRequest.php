<?php

namespace App\Http\Requests;

use App\Models\CarHire;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyCarHireRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('car_hire_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:car_hires,id',
        ];
    }
}
