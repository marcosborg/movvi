<?php

namespace App\Http\Requests;

use App\Models\PeriodsOfTheYear;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyPeriodsOfTheYearRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('periods_of_the_year_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:periods_of_the_years,id',
        ];
    }
}
