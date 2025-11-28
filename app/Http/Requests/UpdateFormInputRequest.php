<?php

namespace App\Http\Requests;

use App\Models\FormInput;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateFormInputRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('form_input_edit');
    }

    public function rules()
    {
        return [
            'label' => [
                'string',
                'required',
            ],
            'name' => [
                'string',
                'required',
            ],
            'type' => [
                'required',
            ],
            'form_name_id' => [
                'required',
                'integer',
            ],
            'position' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
        ];
    }
}
