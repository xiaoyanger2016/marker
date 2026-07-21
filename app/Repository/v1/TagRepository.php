<?php

namespace App\Repository\v1;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TagRepository extends BaseRepository
{
    protected function model(): string
    {
        return Tag::class;
    }

    public function listForUser(User $user): Collection
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function listPopular(int $limit = 20): Collection
    {
        return $this->newQuery()
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
