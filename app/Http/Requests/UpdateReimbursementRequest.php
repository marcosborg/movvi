<?php

namespace App\Http\Requests;

use App\Models\Reimbursement;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateReimbursementRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('reimbursement_edit');
    }

    public function rules()
    {
        return [
            'file' => [
                'required',
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
            ],
        ];
    }
}
