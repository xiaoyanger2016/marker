<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\v1\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(protected ActivityService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'region_code', 'user_id', 'upcoming', 'keyword']);
        $perPage = (int) $request->query('per_page', 20);

        $page = $this->service->list($filters, $perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($a) => $this->present($a)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
                'per_page'     => $page->perPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $activity = $this->service->detail($id);
        $joined = $activity->participants()
            ->whereIn('status', ['joined', 'pending'])
            ->with('user:id,name,avatar')
            ->get()
            ->map(fn ($p) => [
                'user_id'   => $p->user_id,
                'name'      => $p->user->name ?? '匿名',
                'avatar'    => $p->user->avatar ?? null,
                'people'    => $p->people_count,
                'note'      => $p->note,
                'joined_at' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $this->present($activity) + [
                'description' => $activity->description,
                'fee_includes' => $activity->fee_includes,
                'fee_excludes' => $activity->fee_excludes,
                'meeting_point' => $activity->meeting_point,
                'latitude' => $activity->latitude ? (float) $activity->latitude : null,
                'longitude' => $activity->longitude ? (float) $activity->longitude : null,
                'place' => $activity->place ? [
                    'id' => $activity->place->id,
                    'name' => $activity->place->name,
                    'type' => $activity->place->place_type,
                ] : null,
                'route' => $activity->route ? [
                    'id' => $activity->route->id,
                    'name' => $activity->route->name,
                ] : null,
                'participants' => $joined,
                'is_joined' => $request->user()
                    ? $activity->participants()->where('user_id', $request->user()->id)
                        ->whereIn('status', ['joined', 'pending'])->exists()
                    : false,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'cover_image' => 'nullable|string|max:500',

            'place_id' => 'nullable|integer|exists:places,id',
            'route_id' => 'nullable|integer|exists:routes,id',

            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'signup_deadline' => 'nullable|date|before:start_at',

            'meeting_point' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            'max_participants' => 'nullable|integer|min:0|max:255',
            'transport' => 'nullable|string|max:50',
            'fee' => 'nullable|numeric|min:0',
            'fee_includes' => 'nullable|string|max:500',
            'fee_excludes' => 'nullable|string|max:500',

            'region_code' => 'nullable|string|max:20',
            'region_name' => 'nullable|string|max:50',
        ]);

        $activity = $this->service->create($request->user(), $data);
        return response()->json(['data' => $this->present($activity)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:5000',
            'cover_image' => 'nullable|string|max:500',
            'start_at' => 'sometimes|date',
            'end_at' => 'nullable|date|after:start_at',
            'signup_deadline' => 'nullable|date|before:start_at',
            'meeting_point' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'max_participants' => 'nullable|integer|min:0|max:255',
            'transport' => 'nullable|string|max:50',
            'fee' => 'nullable|numeric|min:0',
            'fee_includes' => 'nullable|string|max:500',
            'fee_excludes' => 'nullable|string|max:500',
            'status' => 'sometimes|in:draft,open,full,closed,cancelled',
        ]);

        $activity = $this->service->update($request->user(), $id, $data);
        return response()->json(['data' => $this->present($activity)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->cancel($request->user(), $id);
        return response()->json(['message' => '已取消活动']);
    }

    public function join(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'people_count' => 'nullable|integer|min:1|max:10',
            'note' => 'nullable|string|max:200',
        ]);
        $this->service->join($request->user(), $id, $data['people_count'] ?? 1, $data['note'] ?? null);
        return response()->json(['message' => '报名成功']);
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        $this->service->cancelJoin($request->user(), $id);
        return response()->json(['message' => '已取消报名']);
    }

    protected function present(Activity $a): array
    {
        return [
            'id'            => $a->id,
            'title'         => $a->title,
            'cover_image'   => $a->cover_image,
            'start_at'      => $a->start_at?->toIso8601String(),
            'end_at'        => $a->end_at?->toIso8601String(),
            'signup_deadline' => $a->signup_deadline?->toIso8601String(),
            'meeting_point' => $a->meeting_point,
            'max_participants' => (int) $a->max_participants,
            'joined_count'  => (int) ($a->joined_count ?? 0),
            'remaining'     => $a->remaining,
            'transport'     => $a->transport,
            'fee'           => (float) $a->fee,
            'status'        => $a->status,
            'status_label'  => Activity::STATUSES[$a->status] ?? $a->status,
            'is_expired'    => (bool) $a->is_expired,
            'region_code'   => $a->region_code,
            'region_name'   => $a->region_name,
            'view_count'    => (int) $a->view_count,
            'creator' => $a->user ? [
                'id'     => $a->user->id,
                'name'   => $a->user->name,
                'avatar' => $a->user->avatar,
            ] : null,
            'place' => $a->place ? [
                'id'   => $a->place->id,
                'name' => $a->place->name,
            ] : null,
            'route' => $a->route ? [
                'id'   => $a->route->id,
                'name' => $a->route->name,
            ] : null,
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }
}
