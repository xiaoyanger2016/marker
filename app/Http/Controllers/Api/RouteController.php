<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Services\v1\RouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 线路（自驾/徒步）控制器
 */
class RouteController extends Controller
{
    public function __construct(protected RouteService $service)
    {
    }

    public function publicIndex(Request $request): JsonResponse
    {
        // 兼容旧路径，重定向到 index
        return $this->index($request);
    }

    public function publicShow(Request $request, int $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', Rule::in(['self_drive', 'hiking'])],
            'q' => 'sometimes|nullable|string|max:200',
            'city' => 'sometimes|nullable|string|max:60',
            'featured' => 'sometimes|boolean',
            'sort' => ['sometimes', Rule::in(['heat_score', 'created_at', 'view_count', 'like_count', 'distance_km'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $routes = $user
            ? $this->service->list($user, $filters)
            : $this->service->listForGuest($filters);

        return response()->json([
            'data' => RouteResource::collection($routes),
            'meta' => [
                'current_page' => $routes->currentPage(),
                'last_page' => $routes->lastPage(),
                'total' => $routes->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $route = $user
            ? $this->service->findOne($user, $id)
            : $this->service->findOneForGuest($id);
        return response()->json(['data' => new RouteResource($route)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $route = $this->service->create($request->user(), $data);
        return response()->json(['data' => new RouteResource($route)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validateData($request, $id);
        $route = $this->service->update($request->user(), $id, $data);
        return response()->json(['data' => new RouteResource($route)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);
        return response()->json(['message' => '已删除']);
    }

    public function like(Request $request, int $id): JsonResponse
    {
        $likeCount = $this->service->like($request->user(), $id);
        return response()->json(['message' => '已点赞', 'like_count' => $likeCount]);
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'type' => 'required|in:self_drive,hiking',
            'name' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'rating_label' => ['nullable', Rule::in(['terrible', 'npc', 'nice', 'great', 'amazing'])],
            'difficulty' => 'nullable|in:easy,moderate,hard',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'city' => 'nullable|string|max:60',
            'province' => 'nullable|string|max:60',
            'start_point' => 'nullable|string|max:200',
            'end_point' => 'nullable|string|max:200',
            'best_season' => 'nullable|string|max:100',
            'suitable_for' => 'nullable|string|max:200',
            'is_public' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'requires_order' => 'sometimes|boolean',
            'gear_checklist' => 'nullable|array',
            'gear_checklist.*' => 'string|max:100',
            'safety_notes' => 'nullable|array',
            'safety_notes.*' => 'string|max:200',
            'place_ids' => 'nullable|array',
            'place_ids.*' => 'integer',
        ]);
    }
}
