<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // user_id 为空表示系统预设分类，所有人可见
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('icon', 50)->nullable(); // emoji 或 lucide icon 名
            $table->string('color', 20)->nullable(); // 标签色
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'parent_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
