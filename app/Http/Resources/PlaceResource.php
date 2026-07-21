<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'category_id' => $this->category_id,
            'place_type' => $this->place_type,
            'place_type_label' => \App\Models\Place::PLACE_TYPES[$this->place_type]['label'] ?? null,
            'place_type_icon' => \App\Models\Place::PLACE_TYPES[$this->place_type]['icon'] ?? null,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'district' => $this->district,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'description' => $this->description,
            'phone' => $this->phone,
            'website' => $this->website,
            'business_hours' => $this->business_hours,
            'price_range' => $this->price_range !== null ? (float) $this->price_range : null,
            'rating' => $this->rating,
            'visited_at' => $this->visited_at?->toDateString(),
            'visit_count' => $this->visit_count,
            'is_visited' => $this->is_visited,
            'is_wishlist' => $this->is_wishlist,
            'is_public' => $this->is_public,

            // 停车
            'has_parking' => $this->has_parking,
            'parking_fee_type' => $this->parking_fee_type,
            'parking_fee_type_label' => \App\Models\Place::PARKING_FEE_TYPES[$this->parking_fee_type] ?? null,
            'parking_fee' => $this->parking_fee !== null ? (float) $this->parking_fee : null,
            'parking_notes' => $this->parking_notes,
            'parking_capacity' => $this->parking_capacity,

            // 门票
            'has_ticket' => $this->has_ticket,
            'ticket_price' => $this->ticket_price !== null ? (float) $this->ticket_price : null,
            'ticket_unit' => $this->ticket_unit,
            'ticket_notes' => $this->ticket_notes,

            // 游玩
            'best_season' => $this->best_season,
            'suitable_for' => $this->suitable_for,
            'recommended_duration_minutes' => $this->recommended_duration_minutes,
            'difficulty' => $this->difficulty,
            'difficulty_label' => \App\Models\Place::DIFFICULTY_LEVELS[$this->difficulty]['label'] ?? null,
            'altitude_meters' => $this->altitude_meters,
            'gear_checklist' => $this->gear_checklist,
            'safety_notes' => $this->safety_notes,

            // 联系
            'booking_url' => $this->booking_url,
            'wechat_id' => $this->wechat_id,

            // 媒体
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'cover_url' => $this->whenLoaded('media', function () {
                $cover = $this->media->firstWhere('is_cover', true) ?? $this->media->first();
                return $cover?->url;
            }),

            // 标签
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
            ])),

            // 笔记
            'notes' => NoteResource::collection($this->whenLoaded('notes')),

            // 元信息
            'poi_source' => $this->poi_source,
            'poi_id' => $this->poi_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
