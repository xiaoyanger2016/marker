<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content 关联表:
 *   - content_places: 内容绑定多个地点 (含顺序 + 备注)
 *   - content_tags:   内容绑定多个 tag
 *   - content_media:  内容相册 + 视频集 (m:n)
 *
 * 设计原则:
 *   - 自驾/徒步 用 sequence 字段排序途经点
 *   - 其他 6 类 (单地点) 固定 sequence=0
 *   - 关联表都可独立查询 / 排序 / 删
 */
return new class extends Migration
{
    public function up(): void
    {
        // 内容-地点 m:n (含顺序 + 在内容中的备注)
        Schema::create('content_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(0);    // 0,1,2... 顺序
            $table->text('notes')->nullable();                       // 在内容中此地点的备注
            $table->timestamps();

            $table->unique(['content_id', 'place_id']);
            $table->index(['content_id', 'sequence']);
            $table->index('place_id');
        });

        // 内容-tag m:n
        Schema::create('content_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['content_id', 'tag_id']);
            $table->index('tag_id');
        });

        // 内容-媒体 m:n (相册 + 视频集)
        Schema::create('content_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('role', 20)->default('gallery');          // gallery / video
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'role', 'sequence']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
        Schema::dropIfExists('content_tags');
        Schema::dropIfExists('content_places');
    }
};
