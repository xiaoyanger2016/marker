<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'author' => $this->author,
            'content' => $this->content,
            'cover_url' => $this->cover_url,
            'image_urls' => $this->image_urls,
            'video_urls' => $this->video_urls,
            'published_at' => $this->published_at?->toIso8601String(),
            'place_id' => $this->place_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
