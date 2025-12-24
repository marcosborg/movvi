<?php

namespace App\Http\Requests;

use App\Models\DriversBalance;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateDriversBalanceRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('drivers_balance_edit');
    }

    public function rules()
    {
        $driversBalance = $this->route('drivers_balance');
        $driversBalanceId = is_object($driversBalance) ? $driversBalance->id : $driversBalance;

        return [
            'driver_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
                'unique:drivers_balances,tvde_week_id,' . $driversBalanceId . ',id,driver_id,' . $this->driver_id,
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
