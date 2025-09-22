<?php

namespace App\Http\Requests;

use App\Models\CurrentAccount;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyCurrentAccountRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('current_account_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:current_accounts,id',
        ];
    }
}
