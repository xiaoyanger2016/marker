<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 通用分享表（支持单点分享、收藏集分享、临时分享）
        // 简单的 place/collection 分享走 share_token 字段即可，
        // 这个表留给"权限型"分享（指定用户/邮件邀请等）
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 30); // place / collection
            $table->unsignedBigInteger('resource_id');
            $table->string('token', 64)->unique();
            $table->string('password', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('max_views')->nullable();
            $table->jsonb('permissions')->nullable(); // ['view', 'copy']
            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
            $table->index(['user_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
