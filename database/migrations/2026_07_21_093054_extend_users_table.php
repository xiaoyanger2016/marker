<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('name');
            $table->string('bio')->nullable()->after('avatar');
            $table->boolean('is_admin')->default(false)->after('bio');
            $table->jsonb('preferences')->nullable()->after('is_admin'); // 主题、单位、默认地图等
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'bio', 'is_admin', 'preferences']);
        });
    }
};
