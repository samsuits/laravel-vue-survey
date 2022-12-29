<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Class AuthController
 *
 * @package App\Http\Controllers;
 */
class AuthController
{
    /**
     * Register user
     *
     * @param Request $request
     *
     * @return ResponseFactory|Application|Response
     */
    public function register(Request $request): ResponseFactory|Application|Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|string|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ]
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password'])
        ]);

        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Attempt login
     *
     * @param Request $request
     *
     * @return Application|Response|ResponseFactory
     */
    public function login(Request $request): ResponseFactory|Application|Response
    {
        $credentials = $request->validate([
            'email' => 'required|email|string|exists:users,email',
            'password' => [
                'required',
            ],
            'remember' => 'boolean'
        ]);

        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (!Auth::attempt($credentials, $remember)) {
            return response([
                'error' => 'The provided credentials are not correct'
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Logout
     *
     * @return Response|Application|ResponseFactory
     */
    public function logout(): Response|Application|ResponseFactory
    {
        /** @var User $user */
        $user = Auth::user();
        // Revoke the token that was used to authenticate the current request..
        $user->currentAccessToken()->delete();

        return response([
            'success' => true
        ]);
    }
}
