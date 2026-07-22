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
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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

    public function routes(): HasMany
    {
        return $this->hasMany(\App\Models\Route::class);
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
