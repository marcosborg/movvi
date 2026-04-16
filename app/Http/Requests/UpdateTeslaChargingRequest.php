<?php

namespace App\Http\Requests;

use App\Models\TeslaCharging;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateTeslaChargingRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('tesla_charging_edit');
    }

    public function rules()
    {
        return [
            'value' => [
                'required',
                'numeric',
            ],
            'license' => [
                'required',
                'string',
                'max:50',
            ],
            'datetime' => [
                'required',
                'date',
            ],
            'card_type' => [
                'required',
                'in:Tesla,Continente,Auchan',
            ],
        ];
    }
}
