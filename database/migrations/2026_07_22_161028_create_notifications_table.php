<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 18.4: 通知系统 (用 Laravel 自带的 notifications 表 + morph)
     *  - 触发源: comment_received / activity_joined / vote_received / content_followed
     *  - 每个 user 看自己的 /notifications
     *  - 复用 Laravel 内置 Notifiable trait + Notifications
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable'); // notifiable_type + notifiable_id (user)
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
