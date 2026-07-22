<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 18 · Bug 1: 首页 "§ 02 — 本期精选" 人工 pinned
     *  - admin 后台 ContentResource 列表/编辑/新增 加 toggle (is_picked) + sort
     *  - 没有 picked 时 fallback 随机 N 条 (默认 10)
     *  - 手动 set 的无限量，按 sort 升序排
     */
    public function up(): void
    {
        Schema::create('content_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('note', 200)->nullable(); // 编辑感备注 (可选, 不会渲染到前端, 仅 admin 备忘)
            $table->timestamps();

            // 同一 content 不重复 pick
            $table->unique('content_id');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_picks');
    }
};
