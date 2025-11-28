<?php

namespace App\Http\Requests;

use App\Models\Reimbursement;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyReimbursementRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('reimbursement_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:reimbursements,id',
        ];
    }
}
