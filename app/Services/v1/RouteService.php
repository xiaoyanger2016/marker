<?php

namespace App\Services\v1;

use App\Models\Route;
use App\Models\User;
use App\Repository\v1\RouteRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RouteService
{
    public function __construct(protected RouteRepository $repo)
    {
    }

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $this->repo->listWithFilters($user, $filters);
    }

    public function listForGuest(array $filters): LengthAwarePaginator
    {
        $filters['is_public'] = true;
        return $this->repo->listWithFilters(null, $filters);
    }

    public function findOneForGuest(int $id): Route
    {
        $route = $this->repo->findWithFull($id);
        if (! $route || ! $route->is_public) {
            abort(404);
        }
        $this->repo->incrementView($route);
        return $route;
    }

    public function findOne(User $user, int $id): Route
    {
        $route = $this->repo->findWithFull($id);
        if (! $route) {
            abort(404);
        }
        $this->authorizeView($user, $route);
        $this->repo->incrementView($route);
        return $route;
    }

    public function create(User $user, array $data): Route
    {
        $data['user_id'] = $user->id;
        $data['heat_score'] = 0;

        return DB::transaction(function () use ($data) {
            $route = $this->repo->create($data);
            if (! empty($data['place_ids'])) {
                $this->repo->syncPlaces($route, $data['place_ids'], $data['requires_order'] ?? true);
            }
            $route->load('places');
            return $route;
        });
    }

    public function update(User $user, int $id, array $data): Route
    {
        $route = $this->repo->find($id);
        if (! $route) {
            abort(404);
        }
        $this->authorizeEdit($user, $route);

        DB::transaction(function () use ($route, $data) {
            $route->update($data);
            if (array_key_exists('place_ids', $data)) {
                $this->repo->syncPlaces($route, $data['place_ids'] ?? [], $data['requires_order'] ?? $route->requires_order);
            }
        });

        $route->load('places');
        return $route;
    }

    public function delete(User $user, int $id): void
    {
        $route = $this->repo->find($id);
        if (! $route) {
            abort(404);
        }
        $this->authorizeEdit($user, $route);
        $route->delete();
    }

    public function like(User $user, int $id): int
    {
        $route = $this->repo->find($id);
        if (! $route) {
            abort(404);
        }
        $this->authorizeView($user, $route);
        return $this->repo->incrementLike($route);
    }

    protected function authorizeView(User $user, Route $route): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($route->user_id !== $user->id && ! $route->is_public) {
            abort(404);
        }
    }

    protected function authorizeEdit(User $user, Route $route): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($route->user_id !== $user->id) {
            abort(403);
        }
    }
}
