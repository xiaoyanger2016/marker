<?php

namespace App\Services\v1;

use App\Models\User;
use App\Repository\v1\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(protected UserRepository $repo)
    {
    }

    public function register(array $data): array
    {
        if ($this->repo->findByEmail($data['email'])) {
            throw ValidationException::withMessages(['email' => '该邮箱已被注册']);
        }
        $user = $this->repo->register($data);
        $token = $user->createToken($data['device_name'])->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    public function login(array $data): array
    {
        $user = $this->repo->findByEmail($data['email']);
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new AuthenticationException('邮箱或密码错误');
        }
        $token = $user->createToken($data['device_name'])->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->repo->updateProfile($user, $data);
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (! $this->repo->changePassword($user, $oldPassword, $newPassword)) {
            throw ValidationException::withMessages(['old_password' => '旧密码错误']);
        }
        // 吊销其他 token
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
    }
}
