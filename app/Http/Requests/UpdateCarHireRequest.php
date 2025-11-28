<?php

namespace App\Http\Requests;

use App\Models\CarHire;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateCarHireRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('car_hire_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'amount' => [
                'required',
            ],
            'start_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
        ];
    }
}
