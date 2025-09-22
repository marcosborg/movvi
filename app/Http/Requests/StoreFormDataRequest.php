<?php

namespace App\Http\Requests;

use App\Models\FormData;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreFormDataRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('form_data_create');
    }

    public function rules()
    {
        return [
            'form_name_id' => [
                'required',
                'integer',
            ],
            'data' => [
                'required',
            ],
        ];
    }
}
