<?php

namespace App\Http\Requests;

use App\Models\WeeklyVehicleExpense;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreWeeklyVehicleExpenseRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('weekly_vehicle_expense_create');
    }

    public function rules()
    {
        return [
            'vehicle_item_id' => [
                'required',
                'integer',
            ],
            'driver_id' => [
                'required',
                'integer',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
            ],
            'total_km' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'weekly_km' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'extra_km' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
        ];
    }
}
