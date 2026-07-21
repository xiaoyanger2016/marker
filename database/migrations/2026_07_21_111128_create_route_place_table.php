<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_place', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0); // 0 表示无序（徒步）
            $table->unsignedInteger('stay_minutes')->nullable(); // 建议停留时长
            $table->unsignedInteger('eta_minutes')->nullable(); // 从上一点到此点的驾车时间
            $table->text('notes')->nullable(); // 串联时的小贴士
            $table->timestamps();

            $table->unique(['route_id', 'place_id']);
            $table->index(['route_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_place');
    }
};
