<?php

namespace App\Http\Requests;

use App\Models\CompanyExpense;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCompanyExpenseRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('company_expense_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'company_id' => [
                'required',
                'integer',
            ],
            'weekly_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'recurrence' => [
                'required',
                'in:weekly,once',
            ],
            'category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'supplier' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'start_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                'required_if:recurrence,weekly',
                'nullable',
                'date_format:' . config('panel.date_format'),
                'after_or_equal:start_date',
            ],
            'due_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'paid_at' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'payment_status' => [
                'required',
                'in:pending,paid,cancelled',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'qty' => [
                'required_if:recurrence,weekly',
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
