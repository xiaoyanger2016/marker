<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cover_media_id')->nullable(); // 封面图，弱关联

            $table->string('name', 200);
            $table->string('slug', 200)->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_public')->default(false);
            $table->string('share_token', 64)->nullable()->unique();
            $table->string('share_password', 100)->nullable();
            $table->timestamp('share_expires_at')->nullable();
            $table->unsignedInteger('share_view_count')->default(0);

            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_public']);
            $table->index(['user_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
