<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    /**
     * 系统预设分类（user_id 为空）
     */
    public function scopeSystem($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('user_id');
            if ($user) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }
}
