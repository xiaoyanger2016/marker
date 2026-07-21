<?php

namespace App\Repository\v1;

use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Place 数据访问层
 * - 全部 DB 操作都集中在这里
 * - Service 通过此类与 DB 交互
 */
class PlaceRepository extends BaseRepository
{
    protected function model(): string
    {
        return Place::class;
    }

    /**
     * 可见性 scope：自己 + 公开的（管理员全可见）
     */
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

    /**
     * 列表查询（带筛选 + 搜索 + 排序 + 分页）
     */
    public function listWithFilters(?User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['category', 'tags', 'media']);

        $this->scopeVisibleTo($query, $user);

        // 关键词
        if (! empty($filters['q'])) {
            $this->applySearch($query, $filters['q']);
        }

        // 筛选
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['place_type'])) {
            $query->where('place_type', $filters['place_type']);
        }
        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }
        if (isset($filters['is_wishlist'])) {
            $query->where('is_wishlist', (bool) $filters['is_wishlist']);
        }
        if (isset($filters['is_visited'])) {
            $query->where('is_visited', (bool) $filters['is_visited']);
        }
        if (isset($filters['has_parking'])) {
            $query->where('has_parking', (bool) $filters['has_parking']);
        }
        if (isset($filters['is_public'])) {
            $query->where('is_public', (bool) $filters['is_public']);
        }

        // 标签筛选（满足所有 tag）
        if (! empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', $filters['tag_ids']);
            }, '>=', count($filters['tag_ids']));
        }

        // 排序：雷达 vs 字段排序
        if (! empty($filters['lat']) && ! empty($filters['lng'])) {
            $this->applyNearby($query, $filters['lat'], $filters['lng'], $filters['radius'] ?? 5000);
        } else {
            $sort = $filters['sort'] ?? 'created_at';
            $order = $filters['order'] ?? 'desc';
            $query->orderBy($sort, $order);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * 雷达专用 - 附近 N 米内的点
     */
    public function findNearby(?User $user, float $lat, float $lng, int $radius = 5000, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->newQuery()
            ->with(['category', 'tags', 'media']);

        $this->scopeVisibleTo($query, $user);
        $this->applyNearby($query, $lat, $lng, $radius);
        $query->limit($limit);

        return $query->get();
    }

    /**
     * 游客附近查询
     */
    public function findNearbyPublic(float $lat, float $lng, int $radius, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->newQuery()->with(['category', 'tags', 'media']);
        $this->scopeVisibleTo($query, null);
        $this->applyNearby($query, $lat, $lng, $radius);
        $query->limit($limit);
        return $query->get();
    }

    /**
     * 应用关键词搜索
     */
    protected function applySearch(Builder $query, string $keyword): Builder
    {
        $like = '%' . $keyword . '%';
        return $query->where(function ($q) use ($like) {
            $q->where('name', 'ilike', $like)
              ->orWhere('address', 'ilike', $like)
              ->orWhere('description', 'ilike', $like)
              ->orWhere('city', 'ilike', $like)
              ->orWhere('poi_type', 'ilike', $like)
              ->orWhere('parking_notes', 'ilike', $like)
              ->orWhere('ticket_notes', 'ilike', $like);
        });
    }

    /**
     * 应用 PostGIS 附近查询
     */
    protected function applyNearby(Builder $query, float $lat, float $lng, int $radius): Builder
    {
        return $query
            ->whereRaw(
                'ST_DWithin(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $radius]
            )
            ->orderByRaw(
                'ST_Distance(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
                [$lng, $lat]
            );
    }

    /**
     * 带关系加载
     */
    public function findWithRelations(int $id, array $relations = []): ?Place
    {
        return $this->newQuery()->with($relations)->find($id);
    }

    /**
     * 同步标签
     */
    public function syncTags(Place $place, array $tagIds): void
    {
        $place->tags()->sync($tagIds);
    }

    /**
     * 访问 +1
     */
    public function incrementView(Place $place): void
    {
        $place->increment('view_count');
    }
}
