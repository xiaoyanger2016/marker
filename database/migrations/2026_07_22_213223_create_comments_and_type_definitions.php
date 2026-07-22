<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. comments 表 (polymorphic)
 *    - 对 content 评分 + 评论
 *    - 也可对 place 评论 (polymorphic)
 *    - commentable_type (App\Models\Content / App\Models\Place)
 *
 * 2. content_type_definitions 表 (extensibility)
 *    - 8 大类元数据 (label/icon/color/desc/单vs多地点)
 *    - 新增 type 时插一行即可,UI 自动识别
 *    - Content::TYPES 常量作为 fallback (开发期可见)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 评论 + 评分 (polymorphic) =====
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');                            // commentable_type + commentable_id (加索引)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('body');
            $table->string('rating_label', 20)->nullable();           // 5 档评分 (评论时可同时评分)
            $table->unsignedSmallInteger('rating_value')->nullable();  // 1-5 数字 (兼容老评分)
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index('parent_id');
        });

        // ===== content type 元数据 (extensibility) =====
        Schema::create('content_type_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();                     // self_drive / play_water / ...
            $table->string('label', 60);
            $table->string('icon', 60)->nullable();                   // N°01 或 lucide
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('place_binding', 20)->default('single');   // single | multiple
            $table->string('subtable', 60)->nullable();               // content_self_drive / ...
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_type_definitions');
        Schema::dropIfExists('comments');
    }
};
