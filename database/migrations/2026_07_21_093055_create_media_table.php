<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained('collections')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 20); // image / video
            $table->string('disk', 30)->default('public'); // public / oss / s3
            $table->string('path'); // 相对路径
            $table->string('thumbnail_path')->nullable(); // 视频封面
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0); // bytes
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable(); // 视频秒数
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_cover')->default(false); // 是否为地点封面

            $table->timestamps();

            $table->index(['place_id', 'sort']);
            $table->index(['collection_id', 'sort']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
