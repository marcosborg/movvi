<?php

namespace App\Http\Requests;

use App\Models\ExpenseReimbursement;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreExpenseReimbursementRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('expense_reimbursement_create');
    }

    public function rules()
    {
        return [
            'date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'vehicle_item_id' => [
                'required',
                'integer',
            ],
            'value' => [
                'required',
            ],
        ];
    }
}
