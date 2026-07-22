<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 重构完成:
 *   1. places 表瘦身 — 删 place_type/category_id/is_visited/is_wishlist/is_public/visit_count/visited_at/rating
 *      (这些信息现在由 content 决定)
 *   2. drop place_attributes (数据已灌到 content_*_type 子表)
 *   3. drop routes (合并到 contents)
 *   4. drop route_place pivot
 *   5. drop categories (新架构用 content_type, 不再用 category 区分)
 *
 * 删完后架构:
 *   - Content (8 大类内容贴) — 主表
 *   - 8 个 content_*_type 子表 (type-specific 字段)
 *   - Place — 退到子表位置 (只保留 location 信息)
 *   - content_places (m:n)
 *   - content_tags (m:n)
 *   - content_media (m:n 相册/视频)
 *   - comments (polymorphic)
 *   - content_type_definitions (extensibility)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 0. 解除 activities.route_id 外键 (避免 drop routes 失败) =====
        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropForeign(['route_id']);
            });
        }

        // ===== 1. places 瘦身 =====
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['place_type']);
            $table->dropForeign(['category_id']);
            $table->dropIndex(['user_id', 'is_public']);
            $table->dropIndex(['user_id', 'is_wishlist']);
            $table->dropIndex(['user_id', 'is_visited']);

            $table->dropColumn([
                'place_type',
                'category_id',
                'is_visited',
                'is_wishlist',
                'is_public',
                'visit_count',
                'visited_at',
                'rating',
            ]);
        });

        // ===== 2. drop place_attributes =====
        Schema::dropIfExists('place_attributes');

        // ===== 3. drop routes + route_place =====
        Schema::dropIfExists('route_place');
        Schema::dropIfExists('routes');

        // ===== 4. drop categories =====
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // 复杂回滚不在范围
    }
};
