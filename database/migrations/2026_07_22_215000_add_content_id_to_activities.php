<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Phase 16: route_id 改为 content_id (路由表已 drop)
            if (! Schema::hasColumn('activities', 'content_id')) {
                $table->foreignId('content_id')->nullable()->after('place_id')
                    ->constrained('contents')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'content_id')) {
                $table->dropConstrainedForeignId('content_id');
            }
        });
    }
};
