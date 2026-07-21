<?php

namespace App\Repository\v1;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionRepository extends BaseRepository
{
    protected function model(): string
    {
        return Collection::class;
    }

    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->withCount('places')
            ->orderBy('sort')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findWithPlaces(int $id): ?Collection
    {
        return $this->newQuery()
            ->with(['places' => fn ($q) => $q->with(['category', 'media', 'tags'])
                ->orderBy('collection_place.sort')])
            ->find($id);
    }

    public function attachPlace(Collection $collection, int $placeId, array $pivot = []): void
    {
        $collection->places()->syncWithoutDetaching([
            $placeId => [
                'note' => $pivot['note'] ?? null,
                'sort' => $pivot['sort'] ?? 0,
            ],
        ]);
    }

    public function detachPlace(Collection $collection, int $placeId): void
    {
        $collection->places()->detach($placeId);
    }
}
