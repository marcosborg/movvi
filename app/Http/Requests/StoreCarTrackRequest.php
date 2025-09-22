<?php

namespace App\Http\Requests;

use App\Models\CarTrack;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCarTrackRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('car_track_create');
    }

    public function rules()
    {
        return [
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'license_plate' => [
                'string',
                'nullable',
            ],
        ];
    }
}
