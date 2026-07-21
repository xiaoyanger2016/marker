<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Services\v1\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(protected CollectionService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $list = $this->service->list($request->user());
        return response()->json(['data' => CollectionResource::collection($list)]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $c = $this->service->findOne($request->user(), $id);
        return response()->json(['data' => new CollectionResource($c)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
            'sort' => 'nullable|integer',
        ]);
        $c = $this->service->create($request->user(), $data);
        return response()->json(['data' => new CollectionResource($c)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
            'sort' => 'nullable|integer',
            'share_password' => 'nullable|string|max:100',
            'share_expires_at' => 'nullable|date',
        ]);
        $c = $this->service->update($request->user(), $id, $data);
        return response()->json(['data' => new CollectionResource($c)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);
        return response()->json(['message' => '已删除']);
    }

    public function attachPlace(Request $request, int $id, int $placeId): JsonResponse
    {
        $pivot = $request->validate([
            'note' => 'nullable|string',
            'sort' => 'nullable|integer',
        ]);
        $this->service->attachPlace($request->user(), $id, $placeId, $pivot);
        return response()->json(['message' => '已添加']);
    }

    public function detachPlace(Request $request, int $id, int $placeId): JsonResponse
    {
        $this->service->detachPlace($request->user(), $id, $placeId);
        return response()->json(['message' => '已移除']);
    }
}
