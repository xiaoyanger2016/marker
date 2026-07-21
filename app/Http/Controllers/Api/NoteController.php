<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Services\v1\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(protected NoteService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'place_id' => 'sometimes|integer',
            'source' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $list = $this->service->list($request->user(), $filters);
        return response()->json([
            'data' => NoteResource::collection($list),
            'meta' => [
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
                'current_page' => $list->currentPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $note = $this->service->findOne($request->user(), $id);
        return response()->json(['data' => new NoteResource($note)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validate($request);
        $note = $this->service->create($request->user(), $data);
        return response()->json(['data' => new NoteResource($note)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validate($request);
        $note = $this->service->update($request->user(), $id, $data);
        return response()->json(['data' => new NoteResource($note)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->delete($request->user(), $id);
        return response()->json(['message' => '已删除']);
    }

    protected function validate(Request $request): array
    {
        return $request->validate([
            'place_id' => 'nullable|integer|exists:places,id',
            'title' => 'required|string|max:300',
            'source' => 'required|in:manual,xiaohongshu,dianping,mafengwo,other',
            'source_url' => 'nullable|url|max:500',
            'author' => 'nullable|string|max:100',
            'content' => 'nullable|string',
            'cover_url' => 'nullable|url|max:500',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'url',
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'url',
            'xhs_note_id' => 'nullable|string|max:100',
            'xhs_xsec_token' => 'nullable|string|max:200',
            'xhs_meta' => 'nullable|array',
            'published_at' => 'nullable|date',
        ]);
    }
}
