<?php

namespace App\Services\v1;

use App\Models\Place;
use App\Models\User;
use App\Repository\v1\PlaceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlaceService
{
    public function __construct(protected PlaceRepository $repo)
    {
    }

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $this->repo->listWithFilters($user, $filters);
    }

    /**
     * 游客访问 - 只看公开内容
     */
    public function listForGuest(array $filters): LengthAwarePaginator
    {
        $filters['is_public'] = true;
        return $this->repo->listWithFilters(null, $filters);
    }

    public function findNearbyForGuest(float $lat, float $lng, int $radius, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repo->findNearbyPublic($lat, $lng, $radius, $limit);
    }

    public function findOneForGuest(int $id): Place
    {
        $place = $this->repo->findWithRelations($id, ['category', 'tags', 'media', 'notes']);
        if (! $place || ! $place->is_public) {
            abort(404, '地点不存在');
        }
        $this->repo->incrementView($place);
        return $place;
    }

    public function findNearby(User $user, float $lat, float $lng, int $radius = 5000, int $limit = 50): Collection
    {
        return $this->repo->findNearby($user, $lat, $lng, $radius, $limit);
    }

    public function findOne(User $user, int $id): Place
    {
        $place = $this->repo->findWithRelations($id, ['category', 'tags', 'media', 'notes', 'collections']);
        if (! $place) {
            abort(404, '地点不存在');
        }
        $this->authorizeView($user, $place);
        return $place;
    }

    public function create(User $user, array $data): Place
    {
        $data['user_id'] = $user->id;
        $data['country'] = $data['country'] ?? '中国';

        return DB::transaction(function () use ($data) {
            $place = $this->repo->create($data);
            if (! empty($data['tag_ids'])) {
                $this->repo->syncTags($place, $data['tag_ids']);
            }
            $place->load(['category', 'tags', 'media']);
            return $place;
        });
    }

    public function update(User $user, int $id, array $data): Place
    {
        $place = $this->repo->find($id);
        if (! $place) {
            abort(404);
        }
        $this->authorizeEdit($user, $place);

        DB::transaction(function () use ($place, $data) {
            $place->update($data);
            if (array_key_exists('tag_ids', $data)) {
                $this->repo->syncTags($place, $data['tag_ids'] ?? []);
            }
        });

        $place->load(['category', 'tags', 'media']);
        return $place;
    }

    public function delete(User $user, int $id): void
    {
        $place = $this->repo->find($id);
        if (! $place) {
            abort(404);
        }
        $this->authorizeEdit($user, $place);
        $place->delete();
    }

    public function incrementView(Place $place): void
    {
        $this->repo->incrementView($place);
    }

    public function uploadMedia(User $user, int $placeId, array $data, $file): \App\Models\Media
    {
        $place = $this->repo->find($placeId);
        if (! $place) {
            abort(404);
        }
        $this->authorizeEdit($user, $place);

        $disk = config('filesystems.default_public', 'public');
        $dir = 'places/' . $place->id . '/' . $data['type'] . 's';
        $path = $file->store($dir, $disk);

        $media = new \App\Models\Media();
        $media->place_id = $place->id;
        $media->user_id = $user->id;
        $media->type = $data['type'];
        $media->disk = $disk;
        $media->path = $path;
        $media->size = Storage::disk($disk)->size($path);
        $media->mime = Storage::disk($disk)->mimeType($path);
        $media->title = $data['title'] ?? null;
        $media->caption = $data['caption'] ?? null;
        $media->is_cover = $data['is_cover'] ?? false;
        $media->sort = $place->media()->count();

        if ($data['type'] === 'image') {
            $absPath = Storage::disk($disk)->path($path);
            if (file_exists($absPath)) {
                [$w, $h] = getimagesize($absPath) ?: [null, null];
                $media->width = $w;
                $media->height = $h;
            }
        }

        $media->save();

        if ($media->is_cover) {
            $place->media()->where('id', '!=', $media->id)->update(['is_cover' => false]);
        }

        return $media;
    }

    public function deleteMedia(User $user, int $placeId, int $mediaId): void
    {
        $place = $this->repo->find($placeId);
        if (! $place) {
            abort(404);
        }
        $this->authorizeEdit($user, $place);

        $media = \App\Models\Media::find($mediaId);
        if (! $media || $media->place_id !== $place->id) {
            abort(404, '媒体不属于该地点');
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    // ---- 权限 ----
    protected function authorizeView(User $user, Place $place): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($place->user_id !== $user->id && ! $place->is_public) {
            abort(404, '地点不存在');
        }
    }

    protected function authorizeEdit(User $user, Place $place): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($place->user_id !== $user->id) {
            abort(403, '无权操作此地点');
        }
    }
}
