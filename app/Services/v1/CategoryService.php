<?php

namespace App\Services\v1;

use App\Models\User;
use App\Repository\v1\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(protected CategoryRepository $repo)
    {
    }

    public function list(User $user): Collection
    {
        return $this->repo->listVisible($user);
    }

    public function listForGuest(): Collection
    {
        return $this->repo->listVisibleForGuest();
    }

    public function create(User $user, array $data)
    {
        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['name']);
        return $this->repo->create($data);
    }
}
