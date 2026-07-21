<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title', 300);
            $table->string('source', 30)->default('manual'); // xiaohongshu / dianping / manual
            $table->string('source_url', 500)->nullable(); // 原始链接
            $table->string('author', 100)->nullable(); // 小红书作者
            $table->text('content')->nullable(); // 笔记内容（手动录入或抓取）

            // 小红书解析元数据
            $table->string('xhs_note_id', 100)->nullable();
            $table->string('xhs_xsec_token', 200)->nullable();
            $table->jsonb('xhs_meta')->nullable();

            $table->string('cover_url', 500)->nullable(); // 远程封面
            $table->unsignedBigInteger('cover_media_id')->nullable(); // 本地上传封面
            $table->jsonb('image_urls')->nullable(); // 多图 URL
            $table->jsonb('video_urls')->nullable(); // 视频 URL

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['place_id', 'source']);
            $table->index(['user_id', 'source']);
            $table->index('xhs_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
