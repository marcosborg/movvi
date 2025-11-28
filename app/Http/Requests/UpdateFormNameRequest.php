<?php

namespace App\Http\Requests;

use App\Models\FormName;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateFormNameRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('form_name_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'roles.*' => [
                'integer',
            ],
            'roles' => [
                'array',
            ],
        ];
    }
}
