<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateDefaultCategories;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, CreateDefaultCategories $createDefaultCategories): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $createDefaultCategories) {
            $user = User::create($request->safe()->only(['name', 'email', 'password', 'timezone']));
            $createDefaultCategories($user);

            return $user;
        });

        return $this->withToken($user, $request->input('device_name'))->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $this->withToken($user, $request->input('device_name'));
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function withToken(User $user, ?string $deviceName): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($deviceName ?? 'web')->plainTextToken,
        ]);
    }
}
