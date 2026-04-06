<?php

namespace App\Http\Requests;

use App\Models\Adjustment;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAdjustmentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('adjustment_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'type' => [
                'required',
            ],
            'amount' => [
                'numeric',
                'nullable',
            ],
            'percent' => [
                'string',
                'nullable',
            ],
            'category' => [
                'required',
                'in:' . implode(',', array_keys(Adjustment::CATEGORY_SELECT)),
            ],
            'start_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'end_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'dilution_weeks' => [
                'nullable',
                'integer',
                'min:1',
                'max:52',
            ],
            'drivers.*' => [
                'integer',
            ],
            'drivers' => [
                'array',
            ],
            'company_id' => [
                'required',
                'integer',
            ],
            'affects_vehicle_profitability' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('category') !== Adjustment::CATEGORY_CAUTION_RECEIVED) {
                return;
            }

            $dilutionWeeks = (int) $this->input('dilution_weeks', 1);
            if ($dilutionWeeks > 1 && !$this->filled('start_date')) {
                $validator->errors()->add('start_date', 'A data de inicio e obrigatoria para diluir a caucao por semanas.');
            }
        });
    }
}
