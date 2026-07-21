<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Http\Resources\PlaceResource;
use App\Services\v1\PlaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Place 控制器 - 仅参数校验 + 调 service
 * 业务/DB 全部下沉到 Service / Repository
 */
class PlaceController extends Controller
{
    public function __construct(protected PlaceService $service)
    {
    }

    /**
     * GET /api/v1/places
     * 已登录：自己+公开；游客：只公开
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'sometimes|nullable|string|max:200',
            'category_id' => 'sometimes|nullable|integer',
            'place_type' => 'sometimes|nullable|string|max:60',
            'city' => 'sometimes|nullable|string|max:60',
            'is_wishlist' => 'sometimes|boolean',
            'is_visited' => 'sometimes|boolean',
            'has_parking' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'lat' => 'sometimes|nullable|numeric|between:-90,90',
            'lng' => 'sometimes|nullable|numeric|between:-180,180',
            'radius' => 'sometimes|nullable|integer|min:100|max:500000',
            'tag_ids' => 'sometimes|nullable|array',
            'tag_ids.*' => 'integer',
            'sort' => 'sometimes|in:created_at,visited_at,name,rating,distance',
            'order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // 智能 fallback：未登录时降级为游客模式
        $user = $request->user() ?? null;
        if (! $user) {
            $filters['is_public'] = true;
            $places = $this->service->listForGuest($filters);
        } else {
            $places = $this->service->list($user, $filters);
        }

        return response()->json([
            'data' => PlaceResource::collection($places),
            'meta' => [
                'current_page' => $places->currentPage(),
                'last_page' => $places->lastPage(),
                'per_page' => $places->perPage(),
                'total' => $places->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/places/nearby?lat=&lng=&radius=&limit=
     */
    public function nearby(Request $request): JsonResponse
    {
        $params = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|integer|min:100|max:100000',
            'limit' => 'sometimes|integer|min:1|max:200',
        ]);

        $user = $request->user();
        $places = $user
            ? $this->service->findNearby($user, (float) $params['lat'], (float) $params['lng'], $params['radius'] ?? 5000, $params['limit'] ?? 50)
            : $this->service->findNearbyForGuest((float) $params['lat'], (float) $params['lng'], $params['radius'] ?? 5000, $params['limit'] ?? 50);

        $data = $places->map(function ($p) use ($params) {
            $resource = (new PlaceResource($p))->resolve();
            $resource['distance_meters'] = (int) $p->getDistanceTo((float) $params['lat'], (float) $params['lng']);
            return $resource;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/places/{place}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            $place = $this->service->findOneForGuest($id);
        } else {
            $place = $this->service->findOne($user, $id);
        }
        $this->service->incrementView($place);
        return response()->json(['data' => new PlaceResource($place)]);
    }

    /**
     * POST /api/v1/places
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePlace($request);
        $place = $this->service->create($request->user(), $data);
        return response()->json(['data' => new PlaceResource($place)], 201);
    }

    /**
     * PUT /api/v1/places/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validatePlace($request, $id);
        $place = $this->service->update($request->user(), $id, $data);
        return response()->json(['data' => new PlaceResource($place)]);
    }

    /**
     * DELETE /api/v1/places/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);
        return response()->json(['message' => '已删除']);
    }

    /**
     * POST /api/v1/places/{id}/media
     */
    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:image,video',
            'file' => 'required|file|max:102400',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'is_cover' => 'sometimes|boolean',
        ]);
        $media = $this->service->uploadMedia($request->user(), $id, $data, $request->file('file'));
        return response()->json(['data' => new MediaResource($media)], 201);
    }

    /**
     * DELETE /api/v1/places/{id}/media/{mediaId}
     */
    public function deleteMedia(Request $request, int $id, int $mediaId): JsonResponse
    {
        $this->service->deleteMedia($request->user(), $id, $mediaId);
        return response()->json(['message' => '已删除']);
    }

    // ---- 校验规则 ----
    protected function validatePlace(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|integer|exists:categories,id',
            'place_type' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:60',
            'province' => 'nullable|string|max:60',
            'district' => 'nullable|string|max:60',
            'country' => 'nullable|string|max:60',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'business_hours' => 'nullable|string|max:100',
            'price_range' => 'nullable|numeric|min:0',
            'rating' => 'nullable|integer|between:1,5',
            'rating_label' => 'nullable|in:terrible,npc,nice,great,amazing',
            'visited_at' => 'nullable|date',
            'visit_count' => 'nullable|integer|min:0',
            'is_visited' => 'sometimes|boolean',
            'is_wishlist' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'has_parking' => 'sometimes|boolean',
            'parking_fee_type' => 'nullable|in:free,per_time,per_hour,per_day,unknown',
            'parking_fee' => 'nullable|numeric|min:0',
            'parking_notes' => 'nullable|string',
            'parking_capacity' => 'nullable|integer|min:0',
            'has_ticket' => 'sometimes|boolean',
            'ticket_price' => 'nullable|numeric|min:0',
            'ticket_unit' => 'nullable|string|max:20',
            'ticket_notes' => 'nullable|string',
            'best_season' => 'nullable|string|max:100',
            'suitable_for' => 'nullable|string|max:200',
            'recommended_duration_minutes' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|in:easy,moderate,hard',
            'altitude_meters' => 'nullable|integer|min:0',
            'gear_checklist' => 'nullable|array',
            'gear_checklist.*' => 'string|max:100',
            'safety_notes' => 'nullable|array',
            'safety_notes.*' => 'string|max:200',
            'booking_url' => 'nullable|url|max:255',
            'wechat_id' => 'nullable|string|max:100',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'poi_source' => 'nullable|string|max:30',
            'poi_id' => 'nullable|string|max:200',
            'poi_type' => 'nullable|string|max:100',
        ]);
    }
}
