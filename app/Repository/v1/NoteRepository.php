<?php

namespace App\Repository\v1;

use App\Models\Note;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteRepository extends BaseRepository
{
    protected function model(): string
    {
        return Note::class;
    }

    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->where('user_id', $user->id)
            ->with('place');

        if (! empty($filters['place_id'])) {
            $query->where('place_id', $filters['place_id']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 20);
    }
}
