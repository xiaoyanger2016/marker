<?php

namespace App\Repository\v1;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActivityRepository extends BaseRepository
{
    public function model(): string
    {
        return Activity::class;
    }

    /**
     * 列表：默认仅公开 + open 状态
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $q = $this->newQuery()->with(['user', 'place', 'route'])
            ->withCount(['joinedParticipants']);

        $this->applyFilters($q, $filters);

        return $q->latest('start_at')->paginate($perPage);
    }

    public function findOrFail(int $id): Activity
    {
        return $this->newQuery()
            ->with(['user', 'place', 'route', 'participants.user'])
            ->withCount(['joinedParticipants'])
            ->findOrFail($id);
    }

    /**
     * 用户发起的
     */
    public function byCreator(int $userId)
    {
        return $this->newQuery()
            ->with(['place', 'route'])
            ->withCount('joinedParticipants')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * 用户参与的
     */
    public function byParticipant(int $userId)
    {
        return ActivityParticipant::with('activity.user', 'activity.place', 'activity.route')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function join(int $activityId, int $userId, int $people = 1, ?string $note = null): ActivityParticipant
    {
        return DB::transaction(function () use ($activityId, $userId, $people, $note) {
            // 重复报名 → 覆盖
            return ActivityParticipant::updateOrCreate(
                ['activity_id' => $activityId, 'user_id' => $userId],
                ['status' => 'joined', 'people_count' => $people, 'note' => $note]
            );
        });
    }

    public function cancelJoin(int $activityId, int $userId): bool
    {
        return ActivityParticipant::where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->update(['status' => 'cancelled']) > 0;
    }

    public function incrementView(int $id): void
    {
        Activity::where('id', $id)->increment('view_count');
    }

    /**
     * 应用过滤条件
     */
    protected function applyFilters(Builder $q, array $f): void
    {
        $q->where('is_public', true);

        if (! empty($f['status'])) {
            $q->where('status', $f['status']);
        } else {
            $q->whereIn('status', ['open', 'full']);
        }

        if (! empty($f['region_code'])) {
            $q->where('region_code', $f['region_code']);
        }

        if (! empty($f['user_id'])) {
            $q->where('user_id', $f['user_id']);
        }

        if (! empty($f['upcoming'])) {
            $q->where('start_at', '>=', now());
        }

        if (! empty($f['keyword'])) {
            $kw = $f['keyword'];
            $q->where(function ($x) use ($kw) {
                $x->where('title', 'like', "%{$kw}%")
                  ->orWhere('description', 'like', "%{$kw}%");
            });
        }
    }
}
