<?php

namespace App\Http\Requests;

use App\Models\RegistoEntradaVeiculo;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreRegistoEntradaVeiculoRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('registo_entrada_veiculo_create');
    }

    public function rules()
    {
        return [
            'data_e_horario' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
            'user_id' => [
                'required',
                'integer',
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
            'vehicle_item_id' => [
                'required',
                'integer',
            ],
            'bateria_a_chegada' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'de_bateria_de_saida' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'km_atual' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'frente_do_veiculo_teto_photos' => [
                'array',
            ],
            'frente_do_veiculo_parabrisa_photos' => [
                'array',
            ],
            'frente_do_veiculo_capo_photos' => [
                'array',
            ],
            'frente_do_veiculo_parachoque_photos' => [
                'array',
            ],
            'lateral_esquerda_paralama_diant_photos' => [
                'array',
            ],
            'lateral_esquerda_retrovisor_photos' => [
                'array',
            ],
            'lateral_esquerda_porta_diant_photos' => [
                'array',
            ],
            'lateral_esquerda_porta_tras_photos' => [
                'array',
            ],
            'lateral_esquerda_lateral_photos' => [
                'array',
            ],
            'traseira_tampa_traseira_photos' => [
                'array',
            ],
            'traseira_lanternas_dir_photos' => [
                'array',
            ],
            'traseira_lanterna_esq_photos' => [
                'array',
            ],
            'traseira_parachoque_tras_photos' => [
                'array',
            ],
            'traseira_estepe_photos' => [
                'array',
            ],
            'traseira_macaco_photos' => [
                'array',
            ],
            'traseira_chave_de_roda_photos' => [
                'array',
            ],
            'traseira_triangulo_photos' => [
                'array',
            ],
            'lateral_direita_lateral_photos' => [
                'array',
            ],
            'lateral_direita_porta_tras_photos' => [
                'array',
            ],
            'lateral_direita_porta_diant_photos' => [
                'array',
            ],
            'lateral_direita_retrovisor_photos' => [
                'array',
            ],
            'lateral_direita_paralama_diant_photos' => [
                'array',
            ],
            'cinzeiro_photos' => [
                'array',
            ],
            'copia_de_licenca_de_tvde_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'copia_de_licenca_de_tvde_comentarios' => [
                'string',
                'nullable',
            ],
            'carta_verde_de_seguro_validade_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'carta_verde_de_seguro_validade_comentarios' => [
                'string',
                'nullable',
            ],
            'dua_do_veiculo_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'dua_do_veiculo_comentarios' => [
                'string',
                'nullable',
            ],
            'inspecao_do_veiculo_validade_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'inspecao_do_veiculo_validade_comentarios' => [
                'string',
                'nullable',
            ],
            'contratro_de_prestacao_de_servicos_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'contratro_de_prestacao_de_servicos_comentarios' => [
                'string',
                'nullable',
            ],
            'distico_tvde_colocado_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'distico_tvde_colocado_comentarios' => [
                'string',
                'nullable',
            ],
            'declaracao_amigavel_data' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'declaracao_amigavel_comentarios' => [
                'string',
                'nullable',
            ],
            'possui_triangulo' => [
                'string',
                'nullable',
            ],
        ];
    }
}
