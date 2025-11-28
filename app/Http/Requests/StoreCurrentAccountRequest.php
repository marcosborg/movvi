<?php

namespace App\Http\Requests;

use App\Models\CurrentAccount;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCurrentAccountRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('current_account_create');
    }

    public function rules()
    {
        return [
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
            'data' => [
                'required',
            ],
        ];
    }
}
