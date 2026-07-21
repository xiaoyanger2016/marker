<?php

namespace App\Services\v1;

use App\Models\Tag;
use App\Models\User;
use App\Repository\v1\TagRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TagService
{
    public function __construct(protected TagRepository $repo)
    {
    }

    public function list(User $user): Collection
    {
        return $this->repo->listForUser($user);
    }

    public function listForGuest(): Collection
    {
        return $this->repo->listPopular(20);
    }

    public function createOrFind(User $user, array $data): Tag
    {
        $slug = Str::slug($data['name']);
        $tag = $this->repo->newQuery()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->first();

        if ($tag) {
            return $tag;
        }

        return $this->repo->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? null,
        ]);
    }
}
