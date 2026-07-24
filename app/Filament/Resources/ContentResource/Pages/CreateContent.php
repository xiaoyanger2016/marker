<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use App\Models\Content;
use App\Models\ContentPick;
use App\Models\Media;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    /**
     * Form mount 时给 8 大类子表的 array 字段预填 [] (避免 wire:model 把 CheckboxList 当 boolean 绑)
     */
    public function mount(): void
    {
        $this->form->fill($this->initialFormData());
    }

    protected function initialFormData(): array
    {
        $data = [
            'user_id'     => auth()->id(),
            'is_public'   => true,
            'is_visited'  => false,
            'is_wishlist' => false,
            'is_picked'   => false,
            'pick_sort'   => 0,
            'pick_note'   => '',
        ];
        // 8 大类子表的 array 字段预填 [] (防止 nested path 不存在导致 CheckboxList 走 boolean binding)
        foreach (Content::TYPES as $key => $meta) {
            $data[$key] = [
                'best_season'    => [],
                'gear_checklist' => [],
                'safety_notes'   => [],
            ];
        }
        return $data;
    }

    /**
     * Phase 16/17/18/19：保存前剔除不入主表字段
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
    protected function afterCreate(): void
    {
        $this->syncRelations($this->record, $this->data);
        $this->syncPick($this->record, $this->data);
    }

    protected function syncRelations(Content $content, array $data): void
    {
        // 1. places
        if (! empty($data['places'])) {
            $rows = [];
            foreach (array_values($data['places']) as $i => $p) {
                if (empty($p['place_id'])) continue;
                $rows[$p['place_id']] = [
                    'sequence' => $i,
                    'notes'    => $p['notes'] ?? null,
                ];
            }
            if ($rows) {
                $content->places()->attach($rows);
            }
        }

        // 2. media (gallery + videos)
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
                $sub = new $subClass();
                $sub->content_id = $content->id;
                $sub->fill($data[$type]);
                $sub->save();
            }
        }

        // 4. 关联笔记 (Phase 19)
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
