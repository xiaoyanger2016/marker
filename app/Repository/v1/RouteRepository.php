<?php

namespace App\Repository\v1;

use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RouteRepository extends BaseRepository
{
    protected function model(): string
    {
        return Route::class;
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->where('is_public', true);
        }
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->is_admin) {
                $q->orWhereNotNull('id');
            } else {
                $q->orWhere('is_public', true);
            }
        });
    }

    public function listWithFilters(?User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->withCount('places')
            ->with([
                'places' => fn ($q) => $q->with(['media' => fn ($m) => $m->where('is_cover', true)])->limit(1),
                'media' => fn ($q) => $q->where('is_cover', true)->limit(1),
            ]);

        $this->scopeVisibleTo($query, $user);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_public'])) {
            $query->where('is_public', (bool) $filters['is_public']);
        }
        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }
        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }
        if (! empty($filters['q'])) {
            $like = '%' . $filters['q'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'ilike', $like)
                  ->orWhere('subtitle', 'ilike', $like)
                  ->orWhere('summary', 'ilike', $like)
                  ->orWhere('description', 'ilike', $like);
            });
        }

        $sort = $filters['sort'] ?? 'heat_score';
        $query->orderBy($sort, 'desc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function findWithFull(int $id): ?Route
    {
        return $this->newQuery()
            ->with([
                'user',
                'places' => fn ($q) => $q->with(['category', 'media', 'tags'])
                    ->orderBy('route_place.order'),
                'media' => fn ($q) => $q->orderBy('sort'),
            ])
            ->find($id);
    }

    public function syncPlaces(Route $route, array $placeIds, bool $requiresOrder): void
    {
        $syncData = [];
        foreach ($placeIds as $i => $placeId) {
            $syncData[$placeId] = ['order' => $requiresOrder ? ($i + 1) : 0];
        }
        $route->places()->sync($syncData);
    }

    public function incrementView(Route $route): void
    {
        $route->increment('view_count');
    }

    public function incrementLike(Route $route): int
    {
        $route->increment('like_count');
        $route->refresh();
        $route->recalculateHeat();
        return $route->like_count;
    }
}
