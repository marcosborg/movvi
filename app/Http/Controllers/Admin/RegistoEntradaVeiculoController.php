<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyRegistoEntradaVeiculoRequest;
use App\Http\Requests\StoreRegistoEntradaVeiculoRequest;
use App\Http\Requests\UpdateRegistoEntradaVeiculoRequest;
use App\Models\Driver;
use App\Models\RegistoEntradaVeiculo;
use App\Models\User;
use App\Models\VehicleItem;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class RegistoEntradaVeiculoController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('registo_entrada_veiculo_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $company_id = session()->get('company_id');

        if (!request()->query('status')) {
            $status = 'damage-unfixed';
        } else {
            $status = request()->query('status');
        }

        if ($status == 'damage-unfixed') {
            $registoEntradaVeiculos = RegistoEntradaVeiculo::whereHas('media')->where('reparado', 0)->whereHas('vehicle_item', function ($vehicle_item) use ($company_id) {
                if ($company_id > 0) {
                    $vehicle_item->where('company_id', $company_id);
                }
            })->with(['user', 'driver', 'vehicle_item', 'media'])->get();
        } elseif ($status == 'damage-fixed') {
            $registoEntradaVeiculos = RegistoEntradaVeiculo::whereHas('media')->where('reparado', 1)->whereHas('vehicle_item', function ($vehicle_item) use ($company_id) {
                if ($company_id > 0) {
                    $vehicle_item->where('company_id', $company_id);
                }
            })->with(['user', 'driver', 'vehicle_item', 'media'])->get();
        } else {
            $registoEntradaVeiculos = RegistoEntradaVeiculo::whereHas('vehicle_item', function ($vehicle_item) use ($company_id) {
                if ($company_id > 0) {
                    $vehicle_item->where('company_id', $company_id);
                }
            })->with(['user', 'driver', 'vehicle_item', 'media'])->get();
        }

        return view('admin.registoEntradaVeiculos.index', compact('registoEntradaVeiculos'));
    }

    public function create()
    {
        abort_if(Gate::denies('registo_entrada_veiculo_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.registoEntradaVeiculos.create', compact('drivers', 'users', 'vehicle_items'));
    }

    public function store(StoreRegistoEntradaVeiculoRequest $request)
    {
        $registoEntradaVeiculo = RegistoEntradaVeiculo::create($request->all());

        return redirect('/admin/registo-entrada-veiculos/' . $registoEntradaVeiculo->id . '/edit?step=1');
    }

    public function edit(RegistoEntradaVeiculo $registoEntradaVeiculo)
    {
        abort_if(Gate::denies('registo_entrada_veiculo_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $registoEntradaVeiculo->load('user', 'driver', 'vehicle_item');

        return view('admin.registoEntradaVeiculos.edit', compact('drivers', 'registoEntradaVeiculo', 'users', 'vehicle_items'));
    }

    public function update(UpdateRegistoEntradaVeiculoRequest $request, RegistoEntradaVeiculo $registoEntradaVeiculo)
    {
        $registoEntradaVeiculo->update($request->all());

        if (isset($request->has_photos)) {
            if (count($registoEntradaVeiculo->frente_do_veiculo_teto_photos) > 0) {
                foreach ($registoEntradaVeiculo->frente_do_veiculo_teto_photos as $media) {
                    if (!in_array($media->file_name, $request->input('frente_do_veiculo_teto_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->frente_do_veiculo_teto_photos->pluck('file_name')->toArray();
            foreach ($request->input('frente_do_veiculo_teto_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('frente_do_veiculo_teto_photos');
                }
            }

            if (count($registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos) > 0) {
                foreach ($registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos as $media) {
                    if (!in_array($media->file_name, $request->input('frente_do_veiculo_parabrisa_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos->pluck('file_name')->toArray();
            foreach ($request->input('frente_do_veiculo_parabrisa_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('frente_do_veiculo_parabrisa_photos');
                }
            }

            if (count($registoEntradaVeiculo->frente_do_veiculo_capo_photos) > 0) {
                foreach ($registoEntradaVeiculo->frente_do_veiculo_capo_photos as $media) {
                    if (!in_array($media->file_name, $request->input('frente_do_veiculo_capo_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->frente_do_veiculo_capo_photos->pluck('file_name')->toArray();
            foreach ($request->input('frente_do_veiculo_capo_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('frente_do_veiculo_capo_photos');
                }
            }

            if (count($registoEntradaVeiculo->frente_do_veiculo_parachoque_photos) > 0) {
                foreach ($registoEntradaVeiculo->frente_do_veiculo_parachoque_photos as $media) {
                    if (!in_array($media->file_name, $request->input('frente_do_veiculo_parachoque_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->frente_do_veiculo_parachoque_photos->pluck('file_name')->toArray();
            foreach ($request->input('frente_do_veiculo_parachoque_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('frente_do_veiculo_parachoque_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_esquerda_paralama_diant_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_esquerda_paralama_diant_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_esquerda_paralama_diant_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_esquerda_retrovisor_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_esquerda_retrovisor_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_esquerda_retrovisor_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_esquerda_retrovisor_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_esquerda_retrovisor_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_esquerda_retrovisor_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_esquerda_porta_diant_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_esquerda_porta_diant_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_esquerda_porta_diant_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_esquerda_porta_diant_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_esquerda_porta_diant_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_esquerda_porta_diant_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_esquerda_porta_tras_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_esquerda_porta_tras_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_esquerda_porta_tras_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_esquerda_porta_tras_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_esquerda_porta_tras_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_esquerda_porta_tras_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_esquerda_lateral_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_esquerda_lateral_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_esquerda_lateral_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_esquerda_lateral_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_esquerda_lateral_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_esquerda_lateral_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_tampa_traseira_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_tampa_traseira_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_tampa_traseira_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_tampa_traseira_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_tampa_traseira_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_tampa_traseira_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_lanternas_dir_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_lanternas_dir_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_lanternas_dir_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_lanternas_dir_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_lanternas_dir_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_lanternas_dir_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_lanterna_esq_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_lanterna_esq_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_lanterna_esq_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_lanterna_esq_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_lanterna_esq_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_lanterna_esq_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_parachoque_tras_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_parachoque_tras_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_parachoque_tras_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_parachoque_tras_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_parachoque_tras_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_parachoque_tras_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_estepe_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_estepe_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_estepe_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_estepe_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_estepe_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_estepe_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_macaco_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_macaco_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_macaco_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_macaco_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_macaco_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_macaco_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_chave_de_roda_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_chave_de_roda_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_chave_de_roda_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_chave_de_roda_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_chave_de_roda_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_chave_de_roda_photos');
                }
            }

            if (count($registoEntradaVeiculo->traseira_triangulo_photos) > 0) {
                foreach ($registoEntradaVeiculo->traseira_triangulo_photos as $media) {
                    if (!in_array($media->file_name, $request->input('traseira_triangulo_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->traseira_triangulo_photos->pluck('file_name')->toArray();
            foreach ($request->input('traseira_triangulo_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('traseira_triangulo_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_direita_lateral_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_direita_lateral_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_direita_lateral_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_direita_lateral_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_direita_lateral_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_direita_lateral_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_direita_porta_tras_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_direita_porta_tras_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_direita_porta_tras_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_direita_porta_tras_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_direita_porta_tras_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_direita_porta_tras_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_direita_porta_diant_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_direita_porta_diant_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_direita_porta_diant_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_direita_porta_diant_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_direita_porta_diant_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_direita_porta_diant_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_direita_retrovisor_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_direita_retrovisor_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_direita_retrovisor_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_direita_retrovisor_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_direita_retrovisor_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_direita_retrovisor_photos');
                }
            }

            if (count($registoEntradaVeiculo->lateral_direita_paralama_diant_photos) > 0) {
                foreach ($registoEntradaVeiculo->lateral_direita_paralama_diant_photos as $media) {
                    if (!in_array($media->file_name, $request->input('lateral_direita_paralama_diant_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->lateral_direita_paralama_diant_photos->pluck('file_name')->toArray();
            foreach ($request->input('lateral_direita_paralama_diant_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('lateral_direita_paralama_diant_photos');
                }
            }

            if (count($registoEntradaVeiculo->cinzeiro_photos) > 0) {
                foreach ($registoEntradaVeiculo->cinzeiro_photos as $media) {
                    if (!in_array($media->file_name, $request->input('cinzeiro_photos', []))) {
                        $media->delete();
                    }
                }
            }
            $media = $registoEntradaVeiculo->cinzeiro_photos->pluck('file_name')->toArray();
            foreach ($request->input('cinzeiro_photos', []) as $file) {
                if (count($media) === 0 || !in_array($file, $media)) {
                    $registoEntradaVeiculo->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('cinzeiro_photos');
                }
            }

        }

        if (isset($request->step)) {
            return redirect('/admin/registo-entrada-veiculos/' . $registoEntradaVeiculo->id . '/edit?step=' . $request->step);
        } else {
            return redirect('/admin/registo-entrada-veiculos');
        }


    }

    public function show(RegistoEntradaVeiculo $registoEntradaVeiculo)
    {
        abort_if(Gate::denies('registo_entrada_veiculo_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $registoEntradaVeiculo->load('user', 'driver', 'vehicle_item');

        return view('admin.registoEntradaVeiculos.show', compact('registoEntradaVeiculo'));
    }

    public function destroy(RegistoEntradaVeiculo $registoEntradaVeiculo)
    {
        abort_if(Gate::denies('registo_entrada_veiculo_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $registoEntradaVeiculo->delete();

        return back();
    }

    public function massDestroy(MassDestroyRegistoEntradaVeiculoRequest $request)
    {
        $registoEntradaVeiculos = RegistoEntradaVeiculo::find(request('ids'));

        foreach ($registoEntradaVeiculos as $registoEntradaVeiculo) {
            $registoEntradaVeiculo->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('registo_entrada_veiculo_create') && Gate::denies('registo_entrada_veiculo_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new RegistoEntradaVeiculo();
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }

    public function photos(Request $request)
    {

        $vehicle_item = VehicleItem::where('id', $request->vehicle_item_id)
            ->first()
            ->load('registo_entrada_veiculos.user', 'registo_entrada_veiculos.driver', 'company', 'vehicle_brand', 'vehicle_model');

        if ($vehicle_item) {
            $medias = $vehicle_item->registo_entrada_veiculos->map(function ($registro) {
                return $registro->media;
            })->flatten();
        } else {
            $medias = collect([]);
        }

        return view('admin.registoEntradaVeiculos.photos', compact('vehicle_item', 'medias'));
    }

    public function deleteMedia($media_id)
    {
        Media::find($media_id)->delete();
        return redirect()->back();

    }
}
