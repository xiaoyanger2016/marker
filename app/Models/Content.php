<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 内容/帖子主表
 *
 * 8 大类 (与用户档案 8 content types 一一对应):
 *   - self_drive 自驾线路 (多地点带顺序)
 *   - play_water 玩水点     (单地点)
 *   - hiking 徒步线路       (多地点带顺序)
 *   - paddle 桨板点         (单地点)
 *   - photo 拍照点          (单地点)
 *   - food 美食探店         (单地点)
 *   - camping 露营点        (单地点)
 *   - sunrise_sunset 日出日落 (单地点)
 *
 * 扩展模式: 主表 contents + 8 个 1:1 子表 + content_type_definitions 元数据表
 *           新 type 加新表 + content_type_definitions 加行
 */
class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'cover_media_id',
        'type',
        'title',
        'slug',
        'subtitle',
        'summary',
        'description',
        'rating_label',
        'visit_count',
        'view_count',
        'is_visited',
        'is_wishlist',
        'is_public',
        'published_at',
        'visited_at',
    ];

    protected $casts = [
        'visit_count' => 'integer',
        'view_count'  => 'integer',
        'is_visited'  => 'boolean',
        'is_wishlist' => 'boolean',
        'is_public'   => 'boolean',
        'published_at' => 'datetime',
        'visited_at'   => 'datetime',
    ];

    /**
     * 8 大类元数据 (与 content_type_definitions 表同源)
     * place_binding:
     *   - single: 1 个地点 (sequence=0)
     *   - multiple: N 个地点带 sequence 顺序
     */
    public const TYPES = [
        'self_drive'     => ['label' => '自驾线路', 'icon' => 'N°01', 'color' => '#114B5F', 'place_binding' => 'multiple', 'subtable' => 'content_self_drive'],
        'play_water'     => ['label' => '玩水点',   'icon' => 'N°02', 'color' => '#0D3A4A', 'place_binding' => 'single',   'subtable' => 'content_play_water'],
        'hiking'         => ['label' => '徒步线路', 'icon' => 'N°03', 'color' => '#2D5F3F', 'place_binding' => 'multiple', 'subtable' => 'content_hiking'],
        'paddle'         => ['label' => '桨板点',   'icon' => 'N°04', 'color' => '#0D5C5C', 'place_binding' => 'single',   'subtable' => 'content_paddle'],
        'photo'          => ['label' => '拍照点',   'icon' => 'N°05', 'color' => '#A1461E', 'place_binding' => 'single',   'subtable' => 'content_photo'],
        'food'           => ['label' => '美食探店', 'icon' => 'N°06', 'color' => '#C45626', 'place_binding' => 'single',   'subtable' => 'content_food'],
        'camping'        => ['label' => '露营点',   'icon' => 'N°07', 'color' => '#1A3A3A', 'place_binding' => 'single',   'subtable' => 'content_camping'],
        'sunrise_sunset' => ['label' => '日出日落', 'icon' => 'N°08', 'color' => '#7A4A1A', 'place_binding' => 'single',   'subtable' => 'content_sunrise_sunset'],
    ];

    public const RATING_LABELS = [
        'terrible' => ['label' => '拉垮', 'color' => '#7f1d1d'],
        'npc'      => ['label' => 'NPC',  'color' => '#6b7280'],
        'nice'     => ['label' => 'NICE', 'color' => '#0ea5e9'],
        'great'    => ['label' => '超值', 'color' => '#10b981'],
        'amazing'  => ['label' => '夯',   'color' => '#dc2626'],
    ];

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            if (empty($c->slug) && $c->title) {
                $base = Str::slug($c->title);
                $c->slug = ($c->user_id ?? '0') . '-' . ($base ?: (string) $c->id);
            }
        });
    }

    // ---------- 关联 ----------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * 关联的地点 (按 sequence 排序)
     * - multiple 类型: sequence 0,1,2... 按顺序串联
     * - single 类型:   固定 1 个, sequence=0
     */
    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'content_places')
            ->withPivot('sequence', 'notes')
            ->orderBy('content_places.sequence');
    }

    /**
     * 按 sequence 顺序取的 places (用于详情页)
     */
    public function orderedPlaces()
    {
        return $this->places()->orderBy('content_places.sequence');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'content_tags');
    }

    /**
     * 相册 + 视频集
     * role: 'gallery' | 'video'
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'content_media')
            ->withPivot('role', 'sequence', 'caption')
            ->orderBy('content_media.sequence');
    }

    public function gallery()
    {
        return $this->media()->wherePivot('role', 'gallery');
    }

    public function videos()
    {
        return $this->media()->wherePivot('role', 'video');
    }

    // ---------- 8 个 1:1 子表 (hasOne) ----------

    public function selfDrive(): HasOne
    {
        return $this->hasOne(ContentSelfDrive::class);
    }

    public function hikingData(): HasOne
    {
        return $this->hasOne(ContentHiking::class);
    }

    public function playWater(): HasOne
    {
        return $this->hasOne(ContentPlayWater::class);
    }

    public function paddleData(): HasOne
    {
        return $this->hasOne(ContentPaddle::class);
    }

    public function photoData(): HasOne
    {
        return $this->hasOne(ContentPhoto::class);
    }

    public function foodData(): HasOne
    {
        return $this->hasOne(ContentFood::class);
    }

    public function campingData(): HasOne
    {
        return $this->hasOne(ContentCamping::class);
    }

    public function sunriseSunset(): HasOne
    {
        return $this->hasOne(ContentSunriseSunset::class);
    }

    /**
     * 动态取 type 对应的 1:1 子表 record
     */
    public function subTable(): ?Model
    {
        return match ($this->type) {
            'self_drive'     => $this->selfDrive,
            'play_water'     => $this->playWater,
            'hiking'         => $this->hikingData,
            'paddle'         => $this->paddleData,
            'photo'          => $this->photoData,
            'food'           => $this->foodData,
            'camping'        => $this->campingData,
            'sunrise_sunset' => $this->sunriseSunset,
            default          => null,
        };
    }

    /**
     * 多态评论
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function publicComments(): MorphMany
    {
        return $this->comments()->where('is_public', true)->latest();
    }

    // ---------- Phase 17：评分投票聚合 ----------

    public function votes(): HasMany
    {
        return $this->hasMany(RatingVote::class);
    }

    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getVoteDistributionAttribute(): array
    {
        // 返回 [value => count] (1..5)
        $rows = $this->votes()
            ->selectRaw('rating_value, COUNT(*) as c')
            ->groupBy('rating_value')
            ->pluck('c', 'rating_value')
            ->toArray();
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($rows as $v => $c) {
            $dist[(int) $v] = (int) $c;
        }
        return $dist;
    }

    public function getVoteAvgAttribute(): ?float
    {
        $avg = $this->votes()->avg('rating_value');
        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * 聚合 votes → 写入 rating_label cache
     *  规则：众数 (mode) → label；如果并列，取平均值更接近的档
     */
    public function recomputeRating(): void
    {
        $dist = $this->vote_distribution;
        $total = array_sum($dist);
        if ($total === 0) {
            $this->rating_label = null;
            $this->save();
            return;
        }
        $max = max($dist);
        $candidates = array_keys(array_filter($dist, fn ($c) => $c === $max));
        $avg = $this->vote_avg;
        $value = count($candidates) === 1
            ? (int) $candidates[0]
            : (int) round($avg ?? array_sum($dist) / $total);
        $value = max(1, min(5, $value));
        // value → label (与 Content::RATING_LABELS 顺序对应)
        $map = [1 => 'terrible', 2 => 'npc', 3 => 'nice', 4 => 'great', 5 => 'amazing'];
        $this->rating_label = $map[$value];
        $this->save();
    }

    /**
     * 用户投票 (或改票)
     */
    public function vote(User $user, int $value): void
    {
        $value = max(1, min(5, $value));
        $map = [1 => 'terrible', 2 => 'npc', 3 => 'nice', 4 => 'great', 5 => 'amazing'];
        RatingVote::updateOrCreate(
            ['content_id' => $this->id, 'user_id' => $user->id],
            ['rating_value' => $value, 'rating_label' => $map[$value]],
        );
        $this->recomputeRating();
    }

    public function userVote(User $user): ?RatingVote
    {
        return $this->votes()->where('user_id', $user->id)->first();
    }

    // ---------- 作用域 ----------

    public function scopeOwnedBy(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }

    public function scopeOfType(Builder $q, ?string $type): Builder
    {
        return $q->when($type, fn ($qq) => $qq->where('type', $type));
    }

    public function scopeWishlist(Builder $q): Builder
    {
        return $q->where('is_wishlist', true);
    }

    public function scopeVisited(Builder $q): Builder
    {
        return $q->where('is_visited', true);
    }

    public function scopeSearch(Builder $q, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $q;
        }
        $like = '%' . $keyword . '%';
        return $q->where(function ($qq) use ($like) {
            $qq->where('title', 'ilike', $like)
               ->orWhere('subtitle', 'ilike', $like)
               ->orWhere('summary', 'ilike', $like)
               ->orWhere('description', 'ilike', $like);
        });
    }

    // ---------- helpers ----------

    public function typeMeta(): array
    {
        return self::TYPES[$this->type] ?? ['label' => $this->type, 'icon' => 'N°00', 'color' => '#4A4640', 'place_binding' => 'single', 'subtable' => null];
    }

    public function ratingMeta(): ?array
    {
        return self::RATING_LABELS[$this->rating_label] ?? null;
    }

    public function isMultiplePlaces(): bool
    {
        return ($this->typeMeta()['place_binding'] ?? 'single') === 'multiple';
    }
}
