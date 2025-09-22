<?php

namespace App\Http\Requests;

use App\Models\TeslaCharging;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyTeslaChargingRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('tesla_charging_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:tesla_chargings,id',
        ];
    }
}
