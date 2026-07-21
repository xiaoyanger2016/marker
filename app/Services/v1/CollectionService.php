<?php

namespace App\Services\v1;

use App\Models\Collection;
use App\Models\User;
use App\Repository\v1\CollectionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionService
{
    public function __construct(protected CollectionRepository $repo)
    {
    }

    public function list(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repo->listForUser($user, $perPage);
    }

    public function findOne(User $user, int $id): Collection
    {
        $collection = $this->repo->findWithPlaces($id);
        if (! $collection) {
            abort(404);
        }
        $this->authorizeView($user, $collection);
        return $collection;
    }

    public function create(User $user, array $data): Collection
    {
        $data['user_id'] = $user->id;
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']) ?: 'c' . substr(md5(uniqid()), 0, 6);
        return $this->repo->create($data);
    }

    public function update(User $user, int $id, array $data): Collection
    {
        $collection = $this->repo->find($id);
        if (! $collection) {
            abort(404);
        }
        $this->authorizeEdit($user, $collection);
        $collection->update($data);
        return $collection;
    }

    public function delete(User $user, int $id): void
    {
        $collection = $this->repo->find($id);
        if (! $collection) {
            abort(404);
        }
        $this->authorizeEdit($user, $collection);
        $collection->delete();
    }

    public function attachPlace(User $user, int $collectionId, int $placeId, array $pivot): void
    {
        $collection = $this->repo->find($collectionId);
        if (! $collection) {
            abort(404);
        }
        $this->authorizeEdit($user, $collection);
        $this->repo->attachPlace($collection, $placeId, $pivot);
    }

    public function detachPlace(User $user, int $collectionId, int $placeId): void
    {
        $collection = $this->repo->find($collectionId);
        if (! $collection) {
            abort(404);
        }
        $this->authorizeEdit($user, $collection);
        $this->repo->detachPlace($collection, $placeId);
    }

    protected function authorizeView(User $user, Collection $c): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($c->user_id !== $user->id && ! $c->is_public) {
            abort(404);
        }
    }

    protected function authorizeEdit(User $user, Collection $c): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($c->user_id !== $user->id) {
            abort(403);
        }
    }
}
