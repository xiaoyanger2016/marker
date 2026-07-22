<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content/Post 主表 — 8 大类内容贴
 *
 * 8 大类 = 用户档案的 8 content types
 * 现在 8 类,后续可扩展 (通过 content_type_definitions 动态管理)
 *
 * 字段说明:
 *   - type: 8 enum (self_drive/play_water/hiking/paddle/photo/food/camping/sunrise_sunset)
 *   - title/slug/summary/description: 编辑感内容
 *   - cover_media_id: 封面图 (FK media)
 *   - rating_label: 5 档评分 (拉垮/NPC/NICE/超值/夯)
 *   - 状态: visit_count/view_count/is_visited/is_wishlist/is_public/published_at
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('type', 30);                              // self_drive / play_water / hiking / ...
            $table->string('title', 200);
            $table->string('slug', 200)->nullable();
            $table->string('subtitle', 200)->nullable();
            $table->text('summary')->nullable();                     // 简介 (短)
            $table->text('description')->nullable();                 // 详细描述 (长)
            $table->string('rating_label', 20)->nullable();          // 5 档: terrible/npc/nice/great/legendary

            // 状态
            $table->unsignedInteger('visit_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->boolean('is_visited')->default(false);
            $table->boolean('is_wishlist')->default(false);
            $table->boolean('is_public')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('visited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
            $table->index(['type', 'is_public', 'published_at']);
            $table->index(['user_id', 'is_public']);
            $table->index('slug');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
