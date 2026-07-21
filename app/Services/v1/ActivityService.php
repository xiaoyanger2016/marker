<?php

namespace App\Services\v1;

use App\Models\Activity;
use App\Models\User;
use App\Repository\v1\ActivityRepository;
use Illuminate\Validation\ValidationException;

class ActivityService
{
    public function __construct(protected ActivityRepository $repo)
    {
    }

    public function list(array $filters, int $perPage = 20)
    {
        return $this->repo->paginate($filters, $perPage);
    }

    public function detail(int $id): Activity
    {
        $activity = $this->repo->findOrFail($id);
        $this->repo->incrementView($id);
        return $activity;
    }

    public function create(User $user, array $data): Activity
    {
        $data = $this->normalize($data);
        $data['user_id'] = $user->id;
        $data['status'] = $data['status'] ?? 'open';
        return $this->repo->create($data);
    }

    public function update(User $user, int $id, array $data): Activity
    {
        $activity = $this->repo->findOrFail($id);
        if ($activity->user_id !== $user->id) {
            throw ValidationException::withMessages(['error' => '只能编辑自己发起的活动']);
        }
        $data = $this->normalize($data);
        $activity->update($data);
        return $activity->fresh(['user', 'place', 'route']);
    }

    public function cancel(User $user, int $id): bool
    {
        $activity = $this->repo->findOrFail($id);
        if ($activity->user_id !== $user->id) {
            throw ValidationException::withMessages(['error' => '只能取消自己发起的活动']);
        }
        return (bool) $activity->update(['status' => 'cancelled']);
    }

    public function join(User $user, int $id, int $people = 1, ?string $note = null)
    {
        $activity = $this->repo->findOrFail($id);

        if ($activity->user_id === $user->id) {
            throw ValidationException::withMessages(['error' => '不能报名自己的活动']);
        }
        if ($activity->is_expired) {
            throw ValidationException::withMessages(['error' => '活动已截止/取消']);
        }
        if ($activity->max_participants > 0) {
            $current = $activity->joined_count;
            if ($current + $people > $activity->max_participants) {
                throw ValidationException::withMessages(['error' => '人数已满']);
            }
        }

        $participant = $this->repo->join($id, $user->id, $people, $note);

        // 报名满则自动改状态
        if ($activity->max_participants > 0 && $activity->fresh()->joined_count >= $activity->max_participants) {
            $activity->update(['status' => 'full']);
        }

        return $participant;
    }

    public function cancelJoin(User $user, int $id): bool
    {
        return $this->repo->cancelJoin($id, $user->id);
    }

    /**
     * 数据规范化
     */
    protected function normalize(array $data): array
    {
        if (isset($data['start_at'])) {
            $data['start_at'] = \Carbon\Carbon::parse($data['start_at']);
        }
        if (! empty($data['end_at'])) {
            $data['end_at'] = \Carbon\Carbon::parse($data['end_at']);
        }
        if (! empty($data['signup_deadline'])) {
            $data['signup_deadline'] = \Carbon\Carbon::parse($data['signup_deadline']);
        }
        $data['fee'] = (float) ($data['fee'] ?? 0);
        $data['max_participants'] = (int) ($data['max_participants'] ?? 0);
        return $data;
    }
}
