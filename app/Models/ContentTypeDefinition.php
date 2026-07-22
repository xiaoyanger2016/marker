<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Content 8 大类元数据表 (驱动 admin 动态渲染 + 后续扩展)
 *
 * 扩展方式: 加一行 code='xxx' + 创建对应 content_xxx 子表
 */
class ContentTypeDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'icon',
        'color',
        'description',
        'place_binding',
        'subtable',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort'      => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort');
    }
}
