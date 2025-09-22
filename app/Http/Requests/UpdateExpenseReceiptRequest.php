<?php

namespace App\Http\Requests;

use App\Models\ExpenseReceipt;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateExpenseReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('expense_receipt_edit');
    }

    public function rules()
    {
        return [
            'driver_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'receipts' => [
                'array',
            ],
        ];
    }
}
