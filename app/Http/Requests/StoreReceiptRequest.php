<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('receipt_create');
    }

    public function rules()
    {
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
