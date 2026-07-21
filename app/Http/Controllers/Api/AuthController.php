<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\v1\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 认证控制器
 */
class AuthController extends Controller
{
    public function __construct(protected AuthService $service)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:200',
            'password' => 'required|string|min:8|max:200',
            'device_name' => 'required|string|max:100',
        ]);

        $result = $this->service->register($data);
        return response()->json($result, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $result = $this->service->login($data);
        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user());
        return response()->json(['message' => '已登出']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'avatar' => 'sometimes|nullable|string|max:500',
            'bio' => 'sometimes|nullable|string|max:500',
            'preferences' => 'sometimes|nullable|array',
        ]);

        $user = $this->service->updateProfile($request->user(), $data);
        return response()->json($user);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:200|confirmed',
        ]);

        $this->service->changePassword($request->user(), $data['old_password'], $data['new_password']);
        return response()->json(['message' => '密码已更新']);
    }
}
