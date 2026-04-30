<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('receipt_edit');
    }

    public function rules()
    {
        $receipt = $this->route('receipt');
        $receiptId = is_object($receipt) ? $receipt->id : $receipt;

        return [
            'driver_id' => [
                'required',
                'integer',
                'exists:drivers,id',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
                'exists:tvde_weeks,id',
                Rule::unique('receipts', 'tvde_week_id')
                    ->ignore($receiptId)
                    ->where(fn ($query) => $query
                        ->where('driver_id', $this->input('driver_id'))
                        ->whereNull('deleted_at')),
            ],
            'value' => [
                'required',
            ],
            'file' => [
                'required',
            ],
        ];
    }
}
