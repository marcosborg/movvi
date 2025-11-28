<?php

namespace App\Http\Requests;

use App\Models\WeeklyVehicleExpense;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyWeeklyVehicleExpenseRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('weekly_vehicle_expense_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:weekly_vehicle_expenses,id',
        ];
    }
}
