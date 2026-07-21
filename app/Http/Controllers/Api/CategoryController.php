<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\v1\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $service)
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
        return response()->json(['data' => CategoryResource::collection($list)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
        ]);

        $cat = $this->service->create($request->user(), $data);
        return response()->json(['data' => new CategoryResource($cat)], 201);
    }
}
