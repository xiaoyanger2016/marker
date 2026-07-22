<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 17：内容评分改为多人投票聚合
     *  - rating_votes 表存每条投票 (一人对一内容一票，updateOrCreate)
     *  - content.rating_label 是 cache 字段，recompute() 时从 votes 聚合 (众数 → 5 档)
     *  - rating_vote_count / rating_avg 是冗余 cache，加速列表渲染
     */
    public function up(): void
    {
        Schema::create('rating_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // 5 档值：1=拉垮 2=NPC 3=NICE 4=超值 5=夯
            $table->unsignedTinyInteger('rating_value');
            $table->string('rating_label', 20); // terrible / npc / nice / great / amazing
            $table->timestamps();

            $table->unique(['content_id', 'user_id']); // 一人一票
            $table->index(['content_id', 'rating_value']); // 聚合查询
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_votes');
    }
};
