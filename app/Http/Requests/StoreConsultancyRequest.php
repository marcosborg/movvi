<?php

namespace App\Http\Requests;

use App\Models\Consultancy;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreConsultancyRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('consultancy_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'company_id' => [
                'required',
                'integer',
            ],
            'value' => [
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
        ];
    }
}