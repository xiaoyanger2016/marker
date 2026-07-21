<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description', 'cover_image',
        'place_id', 'route_id',
        'start_at', 'end_at', 'signup_deadline',
        'meeting_point', 'latitude', 'longitude',
        'max_participants', 'transport',
        'fee', 'fee_includes', 'fee_excludes',
        'region_code', 'region_name',
        'status', 'is_public', 'view_count',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'signup_deadline' => 'datetime',
        'fee' => 'decimal:2',
        'is_public' => 'boolean',
    ];

    public const STATUSES = [
        'draft' => '草稿',
        'open' => '招募中',
        'full' => '已满员',
        'closed' => '已截止',
        'cancelled' => '已取消',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function joinedParticipants(): HasMany
    {
        return $this->participants()->whereIn('status', ['joined', 'pending']);
    }

    /**
     * 当前已报名人数
     */
    public function getJoinedCountAttribute(): int
    {
        return $this->joinedParticipants()->sum('people_count');
    }

    /**
     * 剩余名额
     */
    public function getRemainingAttribute(): ?int
    {
        if (! $this->max_participants) {
            return null; // 不限
        }
        return max(0, $this->max_participants - $this->joined_count);
    }

    /**
     * 是否已截止（开始时间已过 / 状态关闭 / 报名截止）
     */
    public function getIsExpiredAttribute(): bool
    {
        if (in_array($this->status, ['closed', 'cancelled'], true)) {
            return true;
        }
        if ($this->start_at && $this->start_at->isPast()) {
            return true;
        }
        if ($this->signup_deadline && now()->gt($this->signup_deadline)) {
            return true;
        }
        return false;
    }
}
