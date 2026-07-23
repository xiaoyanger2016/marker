<?php

namespace App\Services\v1;

use App\Models\Place;
use App\Models\User;
use App\Repository\v1\PlaceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    /**
     * Phase 20：高德 geocode — 详细地址 → 经纬度 + 省/市/区
     *
     * @return array{
     *   success: bool,
     *   longitude: ?float,
     *   latitude: ?float,
     *   province: ?string,
     *   city: ?string,
     *   district: ?string,
     *   formatted_address: ?string,
     *   level: ?string,
     *   message: ?string
     * }
     */
    public function geocodeFromAddress(string $address, ?string $city = null): array
    {
        $result = [
            'success' => false,
            'longitude' => null,
            'latitude' => null,
            'province' => null,
            'city' => null,
            'district' => null,
            'formatted_address' => null,
            'level' => null,
            'message' => null,
        ];

        $address = trim($address);
        if (! $address) {
            $result['message'] = '地址为空';
            return $result;
        }

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            $result['message'] = '高德 API Key 未配置（.env 里的 AMAP_WEB_KEY）';
            return $result;
        }

        try {
            $params = [
                'key'     => $key,
                'address' => $address,
                'output'  => 'json',
            ];
            if ($city) {
                $params['city'] = $city;
            }

            $resp = Http::timeout(8)->get('https://restapi.amap.com/v3/geocode/geo', $params);
            if (! $resp->ok()) {
                $result['message'] = '高德 API 请求失败: HTTP ' . $resp->status();
                return $result;
            }

            $data = $resp->json();
            if (($data['status'] ?? '0') !== '1') {
                $info = $data['info'] ?? '未知';
                $infocode = $data['infocode'] ?? '';
                $hint = match ($infocode) {
                    '10001' => '（高德 API Key 不存在或已禁用，去 lbs.amap.com 控制台查）',
                    '10003' => '（绑定的 IP/域名不在白名单，本机测试把 127.0.0.1 + localhost 加到白名单）',
                    '10004' => '（Key 未启用 Web 服务平台，进控制台 → 添加 Key → 服务平台选「Web 服务」）',
                    '10005' => '（请求过于频繁，被风控，等会儿再试）',
                    '10006' => '（余额不足 / 配额耗尽）',
                    '10007' => '（Key 已过期）',
                    '10008' => '（IP 白名单不匹配，加 127.0.0.1 / localhost）',
                    '10009' => '（请求路径与白名单不符）',
                    '10010' => '（Key 与平台不匹配，本场景需要「Web 服务」平台）',
                    '10011' => '（日调用量超限）',
                    '10012' => '（USER_DAILY_QUERY_OVER_LIMIT）',
                    '10013' => '（USER_RECYCLE_QUERY_OVER_LIMIT）',
                    '10014' => '（云图存量 key 不允许调用地理 API）',
                    '10020' => '（USER_KEY_PLAT_NOMATCH）',
                    '20000' => '（请求参数无效）',
                    '30000' => '（权限不足）',
                    default => '',
                };
                $result['message'] = "高德 geocode 失败: {$info} [infocode: {$infocode}] {$hint}";
                return $result;
            }

            $geocodes = $data['geocodes'] ?? [];
            if (empty($geocodes)) {
                $result['message'] = "高德没找到「{$address}」的坐标。试加城市 (如：浙江省绍兴市西施岩村)";
                return $result;
            }

            $gc = $geocodes[0];
            $location = explode(',', $gc['location'] ?? '0,0');
            $result['success'] = true;
            $result['longitude'] = (float) ($location[0] ?? 0);
            $result['latitude']  = (float) ($location[1] ?? 0);
            $result['province']  = $gc['province']  ?? null;
            $result['city']      = $gc['city']      ?? null;
            $result['district']  = $gc['district']  ?? null;
            $result['formatted_address'] = $gc['formatted_address'] ?? null;
            $result['level']     = $gc['level']     ?? null;

            return $result;
        } catch (ConnectionException $e) {
            $result['message'] = '网络错误: ' . $e->getMessage();
            return $result;
        } catch (\Throwable $e) {
            $result['message'] = '错误: ' . $e->getMessage();
            return $result;
        }
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
