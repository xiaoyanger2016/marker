<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 17：评论支持多模态 (文字 + 图片 + 视频)
     *  - comments.body 已经是 text 字段
     *  - comment_media 关联表 (comment_id, media_id, sequence, kind)
     *  - 一条评论可挂 N 张图 + M 个视频
     *  - 用 media 表复用 storage (统一管理 + 缩略图)
     */
    public function up(): void
    {
        Schema::create('comment_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            // image / video (冗余存一份便于按 type 查询)
            $table->string('kind', 20);
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['comment_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_media');
    }
};
