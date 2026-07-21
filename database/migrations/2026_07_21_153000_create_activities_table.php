<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('发起人');
            $table->string('title', 200)->comment('活动标题');
            $table->text('description')->nullable()->comment('活动详情');
            $table->string('cover_image', 500)->nullable();

            // 关联：可选绑定一个 place 或一个 route
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();

            // 地点/时间/费用
            $table->dateTime('start_at')->comment('出发时间');
            $table->dateTime('end_at')->nullable()->comment('结束时间');
            $table->dateTime('signup_deadline')->nullable()->comment('报名截止时间');
            $table->string('meeting_point', 200)->nullable()->comment('集合地点');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedTinyInteger('max_participants')->default(0)->comment('人数上限，0=不限');
            $table->string('transport', 50)->nullable()->comment('出行方式：自驾/包车/拼车/徒步...');
            $table->decimal('fee', 10, 2)->default(0)->comment('人均费用');
            $table->string('fee_includes', 500)->nullable()->comment('费用包含');
            $table->string('fee_excludes', 500)->nullable()->comment('费用不含');

            $table->string('region_code', 20)->nullable()->comment('所属城市 code');
            $table->string('region_name', 50)->nullable()->comment('所属城市名（冗余方便筛）');

            $table->enum('status', ['draft', 'open', 'full', 'closed', 'cancelled'])->default('open');
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'start_at']);
            $table->index(['region_code', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
