<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\PlaceResource;
use App\Models\Collection;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * 公开访问单点（不需要登录）
     */
    public function showPlace(string $token): JsonResponse
    {
        $place = Place::query()
            ->where('slug', 'like', '%' . $token)
            ->orWhereHas('collections', function ($q) use ($token) {
                $q->where('share_token', $token);
            })
            ->where('is_public', true)
            ->with(['category', 'tags', 'media', 'notes'])
            ->first();

        if (! $place) {
            abort(404, '分享不存在或已关闭');
        }

        return response()->json(['data' => new PlaceResource($place)]);
    }

    /**
     * 公开访问收藏集（不需要登录）
     */
    public function showCollection(Request $request, string $token): JsonResponse
    {
        $collection = Collection::where('share_token', $token)
            ->where('is_public', true)
            ->first();

        if (! $collection) {
            abort(404, '分享不存在');
        }

        if ($collection->isShareExpired()) {
            abort(410, '分享已过期');
        }

        if ($collection->share_password) {
            $provided = $request->input('password', $request->bearerToken());
            // 简化：从 header X-Share-Password 读
            $provided = $request->header('X-Share-Password', $provided);
            if ($provided !== $collection->share_password) {
                return response()->json(['message' => '需要密码', 'require_password' => true], 401);
            }
        }

        $collection->increment('share_view_count');
        $collection->load(['places' => fn ($q) => $q->with(['category', 'media', 'tags'])->orderBy('collection_place.sort')]);

        return response()->json(['data' => new CollectionResource($collection)]);
    }
}
