<?php

namespace App\Http\Requests;

use App\Models\RegistoEntradaVeiculo;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyRegistoEntradaVeiculoRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('registo_entrada_veiculo_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:registo_entrada_veiculos,id',
        ];
    }
}
