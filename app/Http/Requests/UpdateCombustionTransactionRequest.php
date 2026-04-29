<?php

namespace App\Http\Requests;

use App\Models\CombustionTransaction;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateCombustionTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('combustion_transaction_edit');
    }

    public function rules()
    {
        return [
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'card' => [
                'string',
                'required',
            ],
            'supplier' => [
                'required',
                'in:repsol,prio,prio_combustao',
            ],
            'date' => [
                'nullable',
                'date',
            ],
            'amount' => [
                'numeric',
                'required',
            ],
            'total' => [
                'required',
            ],
        ];
    }
}
