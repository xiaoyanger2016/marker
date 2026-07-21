<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeMeta = $this->typeMeta();
        $ratingMeta = $this->ratingMeta();

        $coverUrl = null;
        if ($this->relationLoaded('media') && $this->media) {
            $cover = $this->media->firstWhere('is_cover', true) ?? $this->media->first();
            $coverUrl = $cover?->url;
        }
        if (! $coverUrl) {
            $firstPlace = $this->whenLoaded('places', function () {
                return $this->places->first();
            });
            if ($firstPlace && $firstPlace->relationLoaded('media') && $firstPlace->media) {
                $cover = $firstPlace->media->firstWhere('is_cover', true) ?? $firstPlace->media->first();
                $coverUrl = $cover?->url;
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $typeMeta['label'],
            'type_icon' => $typeMeta['icon'],
            'type_color' => $typeMeta['color'],
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'summary' => $this->summary,
            'description' => $this->description,
            'rating_label' => $this->rating_label,
            'rating_meta' => $ratingMeta,
            'difficulty' => $this->difficulty,
            'distance_km' => $this->distance_km !== null ? (float) $this->distance_km : null,
            'duration_hours' => $this->duration_hours,
            'city' => $this->city,
            'province' => $this->province,
            'start_point' => $this->start_point,
            'end_point' => $this->end_point,
            'best_season' => $this->best_season,
            'suitable_for' => $this->suitable_for,
            'is_public' => $this->is_public,
            'is_featured' => $this->is_featured,
            'requires_order' => $this->requires_order,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'save_count' => $this->save_count,
            'heat_score' => (float) $this->heat_score,
            'cover_url' => $coverUrl,
            'places_count' => $this->whenCounted('places'),
            'places' => PlaceResource::collection($this->whenLoaded('places')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'gear_checklist' => $this->gear_checklist,
            'safety_notes' => $this->safety_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
