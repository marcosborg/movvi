<?php

namespace App\Http\Requests;

use App\Models\UserAlert;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreUserAlertRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('user_alert_create');
    }

    public function rules()
    {
        return [
            'alert_text' => [
                'string',
                'required',
            ],
            'alert_link' => [
                'string',
                'nullable',
                function ($attribute, $value, $fail) {
                    if (blank($value)) {
                        return;
                    }

                    $isInternal = str_starts_with($value, '/') && ! str_starts_with($value, '//');
                    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                    $isExternal = in_array($scheme, ['http', 'https'], true)
                        && filter_var($value, FILTER_VALIDATE_URL);

                    if (! $isInternal && ! $isExternal) {
                        $fail('Indique um endereço completo (https://...) ou um caminho interno iniciado por /.');
                    }
                },
            ],
            'users.*' => [
                'integer',
            ],
            'users' => [
                'array',
            ],
        ];
    }
}
