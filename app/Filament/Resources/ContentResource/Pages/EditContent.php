<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use App\Models\Content;
use App\Models\ContentPick;
use App\Models\Media;
use App\Models\Place;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('查看'),
            Actions\DeleteAction::make()->label('删除'),
        ];
    }

    /**
     * Phase 18 + 19：fill 之前从关联表取 places / notes / media 灌到 form data
     * Resource 上的 static 不会被 EditRecord 自动 call，必须在 Page 上 override
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record) return $data;

        // 关联地点
        $data['places'] = $record->places->map(fn ($p) => [
            'place_id' => $p->id,
            'notes'    => $p->pivot->notes,
        ])->values()->toArray();

        // 关联笔记
        $data['notes'] = $record->notes->map(fn ($n) => [
            'note_id' => $n->id,
            'role'    => $n->pivot->role,
        ])->values()->toArray();

        // 相册 / 视频
        $data['gallery'] = $record->gallery->map(fn ($m) => [
            'path'    => $m->path,
            'caption' => $m->pivot->caption,
        ])->values()->toArray();

        $data['videos'] = $record->videos->map(fn ($m) => [
            'url'     => $m->path,
            'caption' => $m->pivot->caption,
        ])->values()->toArray();

        // 1:1 子表
        $subtable = $record->subTable();
        if ($subtable) {
            $key = $record->type;
            $data[$key] = $subtable->getAttributes();
            unset($data[$key]['id'], $data[$key]['content_id'], $data[$key]['created_at'], $data[$key]['updated_at']);
        }

        // 强制保证 3 个 array 字段是 array (从 DB 读出可能是 JSON string 或 null)
        $type = $data['type'] ?? $record->type;
        if ($type && isset(Content::TYPES[$type])) {
            $subKey = $type;
            if (! isset($data[$subKey]) || ! is_array($data[$subKey])) {
                $data[$subKey] = [];
            }
            foreach (['best_season', 'gear_checklist', 'safety_notes'] as $k) {
                $val = $data[$subKey][$k] ?? null;
                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    $data[$subKey][$k] = is_array($decoded) ? $decoded : [];
                } elseif (! is_array($val)) {
                    $data[$subKey][$k] = [];
                }
            }
        }

        return $data;
    }

    /**
     * Phase 16/17/18/19：保存前剔除不入主表字段
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['places'], $data['gallery'], $data['videos'], $data['notes'],
            $data['cover_upload'], $data['cover_media_path'],
        );
        foreach (Content::TYPES as $meta) {
            if (isset($meta['subtable'])) {
                unset($data[$meta['subtable']]);
            }
        }
        return $data;
    }

    /**
     * 保存后同步 places / gallery / videos / subtable / pick / notes
     */
    protected function afterSave(): void
    {
        $this->syncRelations($this->record, $this->data);
        $this->syncPick($this->record, $this->data);
    }

    protected function syncRelations(Content $content, array $data): void
    {
        // 1. places
        $content->places()->detach();
        if (! empty($data['places'])) {
            $rows = [];
            foreach (array_values($data['places']) as $i => $p) {
                if (empty($p['place_id'])) continue;
                $rows[$p['place_id']] = [
                    'sequence' => $i,
                    'notes'    => $p['notes'] ?? null,
                ];
            }
            $content->places()->attach($rows);
        }

        // 2. media (gallery + videos)
        $content->media()->detach();
        $mediaRows = [];
        $seq = 0;
        foreach (($data['gallery'] ?? []) as $g) {
            if (empty($g['path'])) continue;
            $media = Media::firstOrCreate(
                ['disk' => 'public', 'path' => $g['path']],
                ['type' => 'image', 'user_id' => auth()->id()],
            );
            $mediaRows[$media->id] = [
                'role' => 'gallery', 'sequence' => $seq++, 'caption' => $g['caption'] ?? null,
            ];
        }
        $seq = 0;
        foreach (($data['videos'] ?? []) as $v) {
            if (empty($v['url'])) continue;
            $media = Media::firstOrCreate(
                ['disk' => 'public', 'path' => $v['url']],
                ['type' => 'video', 'user_id' => auth()->id()],
            );
            $mediaRows[$media->id] = [
                'role' => 'video', 'sequence' => $seq++, 'caption' => $v['caption'] ?? null,
            ];
        }
        if ($mediaRows) {
            $content->media()->attach($mediaRows);
        }

        // 3. 1:1 subtable — 根据 type 动态选 sub table model class
        $type = $content->type;
        $subKey = Content::TYPES[$type]['subtable'] ?? null;
        if ($subKey && isset($data[$type]) && is_array($data[$type])) {
            $subClass = 'App\\Models\\' . \Illuminate\Support\Str::studly($subKey);
            if (class_exists($subClass)) {
                $sub = $content->subTable() ?? new $subClass();
                $sub->content_id = $content->id;
                $sub->fill($data[$type]);
                $sub->save();
            }
        }

        // 4. 关联笔记 (Phase 19)
        $content->notes()->detach();
        if (! empty($data['notes']) && is_array($data['notes'])) {
            $noteRows = [];
            foreach (array_values($data['notes']) as $i => $n) {
                if (empty($n['note_id'])) continue;
                $noteRows[$n['note_id']] = [
                    'sequence' => $i,
                    'role'     => $n['role'] ?? 'reference',
                ];
            }
            if ($noteRows) {
                $content->notes()->attach($noteRows);
            }
        }
    }

    protected function syncPick(Content $content, array $data): void
    {
        if (! array_key_exists('is_picked', $data)) return;
        if ($data['is_picked']) {
            ContentPick::updateOrCreate(
                ['content_id' => $content->id],
                [
                    'picked_by' => auth()->id(),
                    'sort'      => (int) ($data['pick_sort'] ?? 0),
                    'note'      => $data['pick_note'] ?? null,
                ],
            );
        } else {
            ContentPick::where('content_id', $content->id)->delete();
        }
    }
}
