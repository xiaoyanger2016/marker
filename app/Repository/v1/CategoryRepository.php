<?php

namespace App\Repository\v1;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    protected function model(): string
    {
        return Category::class;
    }

    public function listVisible(User $user): Collection
    {
        return $this->newQuery()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->where('is_active', true)
            ->withCount('places')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function listVisibleForGuest(): Collection
    {
        return $this->newQuery()
            ->whereNull('user_id')  // 游客只看系统预设
            ->where('is_active', true)
            ->withCount('places')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }
}
