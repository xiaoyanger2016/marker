<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cover_media_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('type', 30); // self_drive / hiking
            $table->string('name', 200);
            $table->string('slug', 200)->nullable();
            $table->string('subtitle', 300)->nullable(); // 副标题/简介
            $table->text('summary')->nullable(); // 卡片用简介
            $table->text('description')->nullable(); // 详情
            $table->string('rating_label', 20)->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->string('city', 60)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('start_point')->nullable(); // 起点
            $table->string('end_point')->nullable(); // 终点
            $table->string('best_season', 100)->nullable();
            $table->string('suitable_for', 200)->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false); // 推荐到首页
            $table->boolean('requires_order')->default(true); // 自驾=有序，徒步=无序
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('save_count')->default(0);
            $table->decimal('heat_score', 10, 2)->default(0); // 综合热度

            $table->jsonb('metadata')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_public']);
            $table->index(['type', 'is_featured']);
            $table->index(['type', 'is_public', 'heat_score']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
