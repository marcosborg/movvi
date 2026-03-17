<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionStepRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'step' => ['required', 'integer', 'min:1', 'max:12'],
            'action' => ['nullable', 'in:save,complete'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],

            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'array'],
            'checklist.cleanliness.external' => ['nullable', 'integer', 'between:0,10'],
            'checklist.cleanliness.interior' => ['nullable', 'integer', 'between:0,10'],
            'checklist.fuel_energy.level' => ['nullable', 'integer', 'between:0,10'],
            'checklist.tire_condition.level' => ['nullable', 'integer', 'between:0,10'],
            'checklist.mileage.odometer_km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'checklist.accessories.via_verde_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.charging_cable_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.charging_adapter_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.spare_tire_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.anti_puncture_kit_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.jack_wrench_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.warning_triangle_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.reflective_vest_present' => ['nullable', 'in:0,1'],
            'checklist.accessories.via_verde_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.charging_cable_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.charging_adapter_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.spare_tire_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.anti_puncture_kit_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.jack_wrench_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.warning_triangle_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.reflective_vest_state' => ['nullable', 'in:good,fair,poor'],
            'checklist.accessories.other_notes' => ['nullable', 'string', 'max:1000'],
            'checklist_photos' => ['nullable', 'array'],
            'checklist_photos.*' => ['nullable', 'array'],
            'checklist_photos.*.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'exterior_photos' => ['nullable', 'array'],
            'exterior_photos.*' => ['nullable', 'array'],
            'exterior_photos.*.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'interior_photos' => ['nullable', 'array'],
            'interior_photos.*' => ['nullable', 'array'],
            'interior_photos.*.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'location' => ['nullable', 'string', 'max:30'],
            'part' => ['nullable', 'string', 'max:120'],
            'part_section' => ['nullable', 'string', 'max:120'],
            'damage_type' => ['nullable', 'string', 'max:40'],
            'damage_notes' => ['nullable', 'string'],
            'damage_photo' => ['nullable', 'array'],
            'damage_photo.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'extra_observations' => ['nullable', 'string'],
            'extra_photos' => ['nullable', 'array'],
            'extra_photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'inspector_name' => ['nullable', 'string', 'max:255'],
            'driver_signature_name' => ['nullable', 'string', 'max:255'],
            'inspector_signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'driver_signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'inspector_signature_data' => ['nullable', 'string'],
            'driver_signature_data' => ['nullable', 'string'],
        ];
    }
}
