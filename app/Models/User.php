<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'is_admin',
        'role',
        'preferences',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public const ROLES = [
        'admin'  => '管理员',
        'editor' => '编辑',
        'user'   => '用户',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin || $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->isAdmin() || $this->role === 'editor';
    }

    // ---------- Phase 18.5: 关注/粉丝 ----------

    /**
     * 我关注的人
     */
    public function followings(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * 关注我的人 (粉丝)
     */
    public function followers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function isFollowing(User $other): bool
    {
        return $this->followings()->where('following_id', $other->id)->exists();
    }

    public function follow(User $other): void
    {
        if ($other->id === $this->id) return;
        $this->followings()->syncWithoutDetaching([$other->id]);
    }

    public function unfollow(User $other): void
    {
        $this->followings()->detach($other->id);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(RatingVote::class);
    }

    /**
     * Filament user menu avatar：返回编辑感 SVG monogram data URI
     * （不用 ui-avatars.com 外链，避免破图黑圆 + 编辑感更稳）
     */
    public function getFilamentAvatarUrl(): ?string
    {
        $name = $this->name ?? 'Reader';
        // 取 name 前两个字符作为 monogram (Eric Yang → EY)
        $parts = preg_split('/\s+/u', trim($name));
        $initials = mb_strtoupper(mb_substr($parts[0] ?? 'M', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
        $initials = mb_substr($initials, 0, 2);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
            . '<rect width="40" height="40" fill="none" stroke="%231A1814" stroke-width="1.2"/>'
            . '<text x="20" y="25" text-anchor="middle" font-family="JetBrains Mono, monospace" font-size="12" font-weight="500" fill="%231A1814" letter-spacing="0.5">'
            . $initials
            . '</text></svg>';

        return 'data:image/svg+xml;utf8,' . $svg;
    }

    /**
     * Filament user menu name：保留原名（侧边 / 顶栏都用）
     */
    public function getFilamentName(): string
    {
        return $this->name ?? 'Reader';
    }
}
