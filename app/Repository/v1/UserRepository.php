<?php

namespace App\Repository\v1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    public function register(array $data): User
    {
        return $this->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();
        return $user;
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (! Hash::check($oldPassword, $user->password)) {
            return false;
        }
        $user->password = Hash::make($newPassword);
        return $user->save();
    }
}
