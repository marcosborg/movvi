<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\FormData;
use App\Models\FormInput;
use App\Models\FormName;
use App\Models\VehicleItem;
use App\Models\Role;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\MediaUploadingTrait;

class FormAssemblyController extends Controller
{

    use MediaUploadingTrait;

    public function index($id = null)
    {
        abort_if(Gate::denies('form_assembly_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $form_names = FormName::all();

        $form_name = FormName::find($id);

        $drivers = Driver::all();

        $vehicle_items = VehicleItem::all();

        $users = User::whereHas('roles', function ($role) {
            $role->where('title', 'Técnico');
        })->get();

        $roles = Role::pluck('title', 'id');

        return view('admin.formAssemblies.index', compact('form_names', 'form_name', 'drivers', 'vehicle_items', 'users', 'roles'));
    }

    public function newField(Request $request)
    {

        $request->validate([
            'name' => 'required|max: 255',
            'label' => 'required|max: 255',
        ]);

        $last_form_input = FormInput::where('form_name_id', $request->form_name_id)->orderBy('position', 'desc')->first();
        if ($last_form_input) {
            $position = $last_form_input->position + 1;
        } else {
            $position = 1;
        }

        $form_input = new FormInput;
        $form_input->name = $request->name;
        $form_input->label = $request->label;
        $form_input->type = $request->type;
        $form_input->form_name_id = $request->form_name_id;
        if ($request->required) {
            $form_input->required = true;
        }
        $form_input->position = $position;
        $form_input->save();

        return redirect()->back()->with('message', 'Success');

    }

    public function sendFormData(Request $request)
    {

        $form_name = FormName::find($request->form_name_id);

        if ($form_name->has_driver) {
            $request->validate([
                'driver_id' => 'required'
            ], [], [
                'driver_id' => 'Driver'
            ]);
        }

        if ($form_name->has_license) {
            $request->validate([
                'vehicle_item_id' => 'required'
            ], [], [
                'vehicle_item_id' => 'License'
            ]);
        }

        if ($form_name->has_technician) {
            $request->validate([
                'user_id' => 'required'
            ], [], [
                'user_id' => 'Nome motorista'
            ]);
        }

        $processedData = [];

        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'photos') !== false) {
                $processedData[$key] = json_encode(explode(',', $value));
            } else {
                $processedData[$key] = $value;
            }
        }

        if ($request->driver_id) {
            $driver = Driver::find($request->driver_id)->load('company');
            $data['driver'] = '<strong>Condutor: </strong>' . $driver->name . ' - ' . $driver->company->name;
        }
        if ($request->vehicle_item_id) {
            $vehicle_item = VehicleItem::find($request->vehicle_item_id)->load('company', 'vehicle_brand', 'vehicle_model');
            $data['vehicle_item'] = '<strong>Viatura: </strong>' . $vehicle_item->license_plate . ' - (' . $vehicle_item->vehicle_brand->name . ' ' . $vehicle_item->vehicle_model->name . ')';
        }
        if ($request->user_id) {
            $user = User::find($request->user_id);
            $data['user'] = '<strong>Técnico: </strong>' . $user->name;
        }

        $data = [];

        foreach ($processedData as $label => $value) {
            if ($label != '_token' && $label != 'form_name_id' && $label != 'driver_id' && $label != 'vehicle_item_id') {
                $data[$label] = $value;
            }
        }

        $data = json_encode($data);

        $form_data = new FormData;
        $form_data->form_name_id = $request->form_name_id;
        $form_data->driver_id = $request->driver_id ?? null;
        $form_data->vehicle_item_id = $request->vehicle_item_id ?? null;
        $form_data->user_id = $request->user_id ?? null;
        $form_data->data = $data;
        $form_data->save();

        return redirect()->back()->with('message', 'Success');
    }

    public function addFormName(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255'
        ]);

        $form_name = new FormName;
        $form_name->name = $request->name;
        $form_name->description = $request->description;
        $form_name->has_driver = $request->has_driver;
        $form_name->has_license = $request->has_license;
        $form_name->has_technician = $request->has_technician;
        $form_name->save();

        $form_name->roles()->sync($request->input('roles', []));

        return redirect()->back()->with('message', 'Success');
    }

    public function deleteFormName($form_name_id)
    {
        FormName::find($form_name_id)->delete();
        return redirect()->back()->with('message', 'Success');
    }

    public function deleteFormInput($form_input_id)
    {
        FormInput::find($form_input_id)->delete();
        return redirect()->back()->with('message', 'Success');
    }

    public function formInputs($form_name_id)
    {
        $form_inputs = FormInput::orderBy('position')->where('form_name_id', $form_name_id)->get();

        return view('admin.formAssemblies.inputs', compact('form_inputs'));
    }

    public function updateInputPosition(Request $request)
    {
        $data = json_decode($request->data);

        foreach ($data as $item) {
            FormInput::find($item->form_input_id)->update(['position' => $item->position]);
        }

    }

    public function uploadFile(Request $request)
    {
        $jsonResponse = $this->storeMedia($request);
        $data = json_decode($jsonResponse->getContent(), true);

        $originalPath = $data['path'] . '/' . $data['name'];

        $targetDir = storage_path('app/public');

        // Certifique-se de que o diretório de destino existe
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true); // Assegure-se de ajustar as permissões conforme necessário
        }

        $newPath = $targetDir . '/' . $data['name'];

        rename($originalPath, $newPath);
        $data['path'] = $newPath;

        return $data;
    }

}
