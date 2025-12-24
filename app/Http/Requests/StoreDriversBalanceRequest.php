<?php

namespace App\Http\Requests;

use App\Models\DriversBalance;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreDriversBalanceRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('drivers_balance_create');
    }

    public function rules()
    {
        return [
            'driver_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
                'unique:drivers_balances,tvde_week_id,NULL,id,driver_id,' . $this->driver_id,
            ],
            'value' => [
                'required',
                'numeric',
            ],
            'last_balance' => [
                'nullable',
                'numeric',
            ],
            'new_balance' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}
