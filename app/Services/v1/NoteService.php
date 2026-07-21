<?php

namespace App\Services\v1;

use App\Models\Note;
use App\Models\User;
use App\Repository\v1\NoteRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteService
{
    public function __construct(protected NoteRepository $repo)
    {
    }

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->repo->listForUser($user, $filters);
    }

    public function findOne(User $user, int $id): Note
    {
        $note = $this->repo->find($id);
        if (! $note) {
            abort(404);
        }
        $this->authorizeView($user, $note);
        return $note;
    }

    public function create(User $user, array $data): Note
    {
        $data['user_id'] = $user->id;
        return $this->repo->create($data);
    }

    public function update(User $user, int $id, array $data): Note
    {
        $note = $this->repo->find($id);
        if (! $note) {
            abort(404);
        }
        $this->authorizeEdit($user, $note);
        $note->update($data);
        return $note;
    }

    public function delete(User $user, int $id): void
    {
        $note = $this->repo->find($id);
        if (! $note) {
            abort(404);
        }
        $this->authorizeEdit($user, $note);
        $note->delete();
    }

    protected function authorizeView(User $user, Note $n): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($n->user_id !== $user->id) {
            abort(404);
        }
    }

    protected function authorizeEdit(User $user, Note $n): void
    {
        if ($user->is_admin) {
            return;
        }
        if ($n->user_id !== $user->id) {
            abort(404);
        }
    }
}
