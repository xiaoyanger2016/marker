<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'share_token' => $this->when($this->is_public, $this->share_token),
            'share_url' => $this->when($this->is_public, $this->share_url),
            'share_view_count' => $this->when($this->is_public, $this->share_view_count),
            'places_count' => $this->whenCounted('places'),
            'cover_url' => $this->whenLoaded('places', function () {
                $first = $this->places->first();
                if (! $first) {
                    return null;
                }
                $cover = $first->media->firstWhere('is_cover', true) ?? $first->media->first();
                return $cover?->url;
            }),
            'places' => PlaceResource::collection($this->whenLoaded('places')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
