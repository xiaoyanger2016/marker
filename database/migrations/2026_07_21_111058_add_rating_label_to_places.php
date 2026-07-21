<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // 5 档评分：拉垮 / NPC / NICE / 超值 / 夯
            $table->string('rating_label', 20)->nullable()->after('rating');
            $table->index('rating_label');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['rating_label']);
            $table->dropColumn('rating_label');
        });
    }
};
