<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMenuLinkRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('website_access');
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'url' => ['required', 'string'],
            'target' => ['nullable', 'string'],
        ];
    }
}
