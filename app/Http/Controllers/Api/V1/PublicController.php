<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PageForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string'],
            'rgpd' => ['accepted'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        PageForm::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'message' => $request->message,
            'rgpd' => $request->boolean('rgpd'),
        ]);

        return response()->json([
            'message' => 'Mensagem enviada com sucesso.',
        ]);
    }
}
