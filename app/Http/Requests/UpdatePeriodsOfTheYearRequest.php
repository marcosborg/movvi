<?php

namespace App\Http\Requests;

use App\Models\PeriodsOfTheYear;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdatePeriodsOfTheYearRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('periods_of_the_year_edit');
    }

    public function rules()
    {
        return [
            'start_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'type' => [
                'required',
            ],
        ];
    }
}
