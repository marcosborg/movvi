<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RegistoEntradaVeiculo extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory;

    public $table = 'registo_entrada_veiculos';

    protected $dates = [
        'data_e_horario',
        'copia_de_licenca_de_tvde_data',
        'carta_verde_de_seguro_validade_data',
        'dua_do_veiculo_data',
        'inspecao_do_veiculo_validade_data',
        'contratro_de_prestacao_de_servicos_data',
        'distico_tvde_colocado_data',
        'declaracao_amigavel_data',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'frente_do_veiculo_teto_photos',
        'frente_do_veiculo_parabrisa_photos',
        'frente_do_veiculo_capo_photos',
        'frente_do_veiculo_parachoque_photos',
        'lateral_esquerda_paralama_diant_photos',
        'lateral_esquerda_retrovisor_photos',
        'lateral_esquerda_porta_diant_photos',
        'lateral_esquerda_porta_tras_photos',
        'lateral_esquerda_lateral_photos',
        'traseira_tampa_traseira_photos',
        'traseira_lanternas_dir_photos',
        'traseira_lanterna_esq_photos',
        'traseira_parachoque_tras_photos',
        'traseira_estepe_photos',
        'traseira_macaco_photos',
        'traseira_chave_de_roda_photos',
        'traseira_triangulo_photos',
        'lateral_direita_lateral_photos',
        'lateral_direita_porta_tras_photos',
        'lateral_direita_porta_diant_photos',
        'lateral_direita_retrovisor_photos',
        'lateral_direita_paralama_diant_photos',
        'cinzeiro_photos',
    ];

    protected $fillable = [
        'data_e_horario',
        'user_id',
        'driver_id',
        'vehicle_item_id',
        'bateria_a_chegada',
        'de_bateria_de_saida',
        'km_atual',
        'frente_do_veiculo_teto',
        'frente_do_veiculo_parabrisa',
        'frente_do_veiculo_capo',
        'frente_do_veiculo_parachoque',
        'frente_do_veiculo_nada_consta',
        'frente_do_veiculo_obs',
        'lateral_esquerda_paralama_diant',
        'lateral_esquerda_retrovisor',
        'lateral_esquerda_porta_diant',
        'lateral_esquerda_porta_tras',
        'lateral_esquerda_lateral',
        'lateral_esquerda_nada_consta',
        'lateral_esquerda_obs',
        'traseira_mala',
        'traseira_farol_dir',
        'traseira_farol_esq',
        'traseira_parachoque_tras',
        'traseira_pneu_reserva',
        'traseira_macaco',
        'traseira_chave_de_roda',
        'traseira_triangulo',
        'traseira_nada_consta',
        'traseira_obs',
        'lateral_direita_lateral',
        'lateral_direita_porta_tras',
        'lateral_direita_porta_diant',
        'lateral_direita_retrovisor',
        'lateral_direita_paralama_diant',
        'lateral_direita_nada_consta',
        'lateral_direita_obs',
        'cinzeiro_sim',
        'cinzeiro_nada_consta',
        'aspiracao_bancos_frente',
        'aspiracao_bancos_tras',
        'aspiracao_tapetes_e_chao_frente',
        'aspiracao_tapetes_e_chao_tras',
        'limpeza_e_brilho_de_plasticos_carro',
        'ambientador_de_carro',
        'limpeza_vidros_interiores',
        'retirar_os_objetos_pessoais_existentes_no_carro',
        'verificacao_de_luzes_no_painel',
        'verificacao_de_necessidade_de_lavagem_estofos',
        'check_list_aspiracao_obs',
        'copia_de_licenca_de_tvde',
        'copia_de_licenca_de_tvde_data',
        'copia_de_licenca_de_tvde_comentarios',
        'carta_verde_de_seguro_validade',
        'carta_verde_de_seguro_validade_data',
        'carta_verde_de_seguro_validade_comentarios',
        'dua_do_veiculo',
        'dua_do_veiculo_data',
        'dua_do_veiculo_comentarios',
        'inspecao_do_veiculo_validade',
        'inspecao_do_veiculo_validade_data',
        'inspecao_do_veiculo_validade_comentarios',
        'contratro_de_prestacao_de_servicos',
        'contratro_de_prestacao_de_servicos_data',
        'contratro_de_prestacao_de_servicos_comentarios',
        'distico_tvde_colocado',
        'distico_tvde_colocado_data',
        'distico_tvde_colocado_comentarios',
        'declaracao_amigavel',
        'declaracao_amigavel_data',
        'declaracao_amigavel_comentarios',
        'aplicacao_de_agua_por_todo_o_carro',
        'passagem_de_agua_em_todo_o_carro',
        'aplicacao_de_champo_em_todo_o_carro',
        'esfregar_todo_o_carro_com_a_escova',
        'retirar_com_agua',
        'verificar_sujidades_ainda_existentes',
        'limpeza_de_jantes',
        'possui_triangulo',
        'possui_extintor',
        'banco_elevatorio_crianca',
        'colete',
        'tratado',
        'comentarios',
        'reparado',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /*

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    */

    public function getDataEHorarioAttribute($value)
    {
        return $value ? Carbon::createFromFormat('Y-m-d H:i:s', $value)->format(config('panel.date_format') . ' ' . config('panel.time_format')) : null;
    }

    public function setDataEHorarioAttribute($value)
    {
        $this->attributes['data_e_horario'] = $value ? Carbon::createFromFormat(config('panel.date_format') . ' ' . config('panel.time_format'), $value)->format('Y-m-d H:i:s') : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle_item()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }

    public function getFrenteDoVeiculoTetoPhotosAttribute()
    {
        return $this->getMedia('frente_do_veiculo_teto_photos');
    }

    public function getFrenteDoVeiculoParabrisaPhotosAttribute()
    {
        return $this->getMedia('frente_do_veiculo_parabrisa_photos');
    }

    public function getFrenteDoVeiculoCapoPhotosAttribute()
    {
        return $this->getMedia('frente_do_veiculo_capo_photos');
    }

    public function getFrenteDoVeiculoParachoquePhotosAttribute()
    {
        return $this->getMedia('frente_do_veiculo_parachoque_photos');
    }

    public function getLateralEsquerdaParalamaDiantPhotosAttribute()
    {
        return $this->getMedia('lateral_esquerda_paralama_diant_photos');
    }

    public function getLateralEsquerdaRetrovisorPhotosAttribute()
    {
        return $this->getMedia('lateral_esquerda_retrovisor_photos');
    }

    public function getLateralEsquerdaPortaDiantPhotosAttribute()
    {
        return $this->getMedia('lateral_esquerda_porta_diant_photos');
    }

    public function getLateralEsquerdaPortaTrasPhotosAttribute()
    {
        return $this->getMedia('lateral_esquerda_porta_tras_photos');
    }

    public function getLateralEsquerdaLateralPhotosAttribute()
    {
        return $this->getMedia('lateral_esquerda_lateral_photos');
    }

    public function getTraseiraTampaTraseiraPhotosAttribute()
    {
        return $this->getMedia('traseira_tampa_traseira_photos');
    }

    public function getTraseiraLanternasDirPhotosAttribute()
    {
        return $this->getMedia('traseira_lanternas_dir_photos');
    }

    public function getTraseiraLanternaEsqPhotosAttribute()
    {
        return $this->getMedia('traseira_lanterna_esq_photos');
    }

    public function getTraseiraParachoqueTrasPhotosAttribute()
    {
        return $this->getMedia('traseira_parachoque_tras_photos');
    }

    public function getTraseiraEstepePhotosAttribute()
    {
        return $this->getMedia('traseira_estepe_photos');
    }

    public function getTraseiraMacacoPhotosAttribute()
    {
        return $this->getMedia('traseira_macaco_photos');
    }

    public function getTraseiraChaveDeRodaPhotosAttribute()
    {
        return $this->getMedia('traseira_chave_de_roda_photos');
    }

    public function getTraseiraTrianguloPhotosAttribute()
    {
        return $this->getMedia('traseira_triangulo_photos');
    }

    public function getLateralDireitaLateralPhotosAttribute()
    {
        return $this->getMedia('lateral_direita_lateral_photos');
    }

    public function getLateralDireitaPortaTrasPhotosAttribute()
    {
        return $this->getMedia('lateral_direita_porta_tras_photos');
    }

    public function getLateralDireitaPortaDiantPhotosAttribute()
    {
        return $this->getMedia('lateral_direita_porta_diant_photos');
    }

    public function getLateralDireitaRetrovisorPhotosAttribute()
    {
        return $this->getMedia('lateral_direita_retrovisor_photos');
    }

    public function getLateralDireitaParalamaDiantPhotosAttribute()
    {
        return $this->getMedia('lateral_direita_paralama_diant_photos');
    }

    public function getCinzeiroPhotosAttribute()
    {
        return $this->getMedia('cinzeiro_photos');
    }

    public function getCopiaDeLicencaDeTvdeDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setCopiaDeLicencaDeTvdeDataAttribute($value)
    {
        $this->attributes['copia_de_licenca_de_tvde_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getCartaVerdeDeSeguroValidadeDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setCartaVerdeDeSeguroValidadeDataAttribute($value)
    {
        $this->attributes['carta_verde_de_seguro_validade_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDuaDoVeiculoDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDuaDoVeiculoDataAttribute($value)
    {
        $this->attributes['dua_do_veiculo_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getInspecaoDoVeiculoValidadeDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setInspecaoDoVeiculoValidadeDataAttribute($value)
    {
        $this->attributes['inspecao_do_veiculo_validade_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getContratroDePrestacaoDeServicosDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setContratroDePrestacaoDeServicosDataAttribute($value)
    {
        $this->attributes['contratro_de_prestacao_de_servicos_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDisticoTvdeColocadoDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDisticoTvdeColocadoDataAttribute($value)
    {
        $this->attributes['distico_tvde_colocado_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDeclaracaoAmigavelDataAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDeclaracaoAmigavelDataAttribute($value)
    {
        $this->attributes['declaracao_amigavel_data'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }
}
