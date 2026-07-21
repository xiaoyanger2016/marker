<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'mime' => $this->mime,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'title' => $this->title,
            'caption' => $this->caption,
            'is_cover' => $this->is_cover,
            'sort' => $this->sort,
        ];
    }
}
