<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        $credentials = request(['email', 'password']);
        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'password' => [
                        'Invalid credentials'
                    ],
                ]
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        $authToken = $user->createToken('auth-token')->plainTextToken;
        $user->load('roles');
        $driver = Driver::with('company')
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'access_token' => $authToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('title')->values(),
            ],
            'driver' => $driver ? [
                'id' => $driver->id,
                'code' => $driver->code,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'company' => $driver->company ? [
                    'id' => $driver->company->id,
                    'name' => $driver->company->name,
                ] : null,
            ] : null,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::sendResetLink([
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'Se existir uma conta com esse email, enviamos um link para recuperar a password.',
        ]);
    }
}
