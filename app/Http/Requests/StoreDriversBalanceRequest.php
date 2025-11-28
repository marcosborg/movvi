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
            ],
            'value' => [
                'required',
            ],
            'balance' => [
                'required',
            ],
        ];
    }
}
