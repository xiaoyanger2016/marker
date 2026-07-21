<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Services\v1\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(protected TagService $service)
    {
    }

    public function publicIndex(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $list = $user ? $this->service->list($user) : $this->service->listForGuest();
        return response()->json(['data' => TagResource::collection($list)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'nullable|string|max:20',
        ]);
        $tag = $this->service->createOrFind($request->user(), $data);
        return response()->json(['data' => new TagResource($tag)], 201);
    }
}
