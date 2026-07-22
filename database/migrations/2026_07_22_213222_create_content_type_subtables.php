<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8 大类 type-specific sub-tables (1:1 with contents)
 *
 * 每个 type 一个子表,差异字段放这里:
 *   - 自驾线路: distance_km / altitude_meters / road_condition / gas_stations / waypoints / two_foot_route_id ...
 *   - 徒步线路: distance_km / elevation_gain / difficulty / route_type / waypoints / two_foot_route_id ...
 *   - 玩水点: water_type / water_depth / is_swimmable / has_lifeguard ...
 *   - 桨板点: water_depth / water_current / rental_available ...
 *   - 拍照点: best_time / best_light / viewpoint_count / is_drone_allowed ...
 *   - 美食: price_per_person / cuisine_type / signature_dishes / reservation ...
 *   - 露营点: altitude_meters / has_water / has_toilet / fire_allowed ...
 *   - 日出日落: direction / best_time / viewpoint_count ...
 *
 * 扩展思路: 新增 type 时新建一个 content_{new_type} 表 (1:1 with contents) + 在 Content::TYPES 加 entry
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 1. 自驾线路 =====
        Schema::create('content_self_drive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('altitude_meters')->nullable();
            $table->string('difficulty', 20)->nullable();              // easy/moderate/hard
            $table->string('road_condition', 20)->nullable();         // paved/mostly_paved/mixed/offroad
            $table->jsonb('best_season')->nullable();                 // [spring,summer,...]
            $table->jsonb('gas_stations')->nullable();                // [{name, km}]
            $table->jsonb('waypoints')->nullable();                   // 沿途途经点 (sequence 0,1,2)
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->string('two_foot_route_id', 60)->nullable();      // 两步路线路 ID
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 2. 徒步线路 =====
        Schema::create('content_hiking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('altitude_meters')->nullable();
            $table->unsignedInteger('elevation_gain')->nullable();     // 累计爬升
            $table->string('difficulty', 20)->nullable();
            $table->string('route_type', 20)->nullable();             // loop / out_back / one_way
            $table->jsonb('best_season')->nullable();
            $table->jsonb('waypoints')->nullable();                   // 多个标记点
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->string('two_foot_route_id', 60)->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 3. 玩水点 =====
        Schema::create('content_play_water', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('water_type', 30)->nullable();              // lake/river/sea/pool/reservoir
            $table->string('water_depth', 30)->nullable();
            $table->boolean('is_swimmable')->default(true);
            $table->boolean('is_free')->default(true);
            $table->string('parking', 30)->nullable();                 // free/paid/limited/no
            $table->string('ticket', 60)->nullable();                 // "¥20/人"
            $table->boolean('has_lifeguard')->default(false);
            $table->jsonb('best_season')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 4. 桨板点 =====
        Schema::create('content_paddle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('water_depth', 30)->nullable();
            $table->string('water_current', 20)->nullable();          // calm/mild/moderate/strong
            $table->string('difficulty', 20)->nullable();
            $table->boolean('rental_available')->default(false);
            $table->string('best_time', 60)->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 5. 拍照点 =====
        Schema::create('content_photo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('best_time', 60)->nullable();
            $table->string('best_light', 60)->nullable();
            $table->unsignedSmallInteger('viewpoint_count')->nullable();
            $table->boolean('is_drone_allowed')->default(false);
            $table->boolean('permit_required')->default(false);
            $table->string('parking', 30)->nullable();
            $table->jsonb('best_season')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 6. 美食探店 =====
        Schema::create('content_food', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->decimal('price_per_person', 8, 2)->nullable();
            $table->string('cuisine_type', 60)->nullable();
            $table->string('business_hours', 60)->nullable();
            $table->jsonb('signature_dishes')->nullable();
            $table->string('reservation', 100)->nullable();
            $table->string('parking', 30)->nullable();
            $table->string('contact', 60)->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 7. 露营点 =====
        Schema::create('content_camping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->unsignedInteger('altitude_meters')->nullable();
            $table->boolean('is_free')->default(true);
            $table->boolean('has_water')->default(false);
            $table->boolean('has_toilet')->default(false);
            $table->boolean('fire_allowed')->default(false);
            $table->boolean('has_signal')->default(false);
            $table->string('parking', 30)->nullable();
            $table->jsonb('best_season')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });

        // ===== 8. 日出日落 =====
        Schema::create('content_sunrise_sunset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('direction', 20)->nullable();              // east (日出) / west (日落) / both
            $table->string('best_time', 60)->nullable();
            $table->unsignedSmallInteger('viewpoint_count')->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->boolean('is_drone_allowed')->default(false);
            $table->string('parking', 30)->nullable();
            $table->jsonb('best_season')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
            $table->timestamps();
            $table->unique('content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_sunrise_sunset');
        Schema::dropIfExists('content_camping');
        Schema::dropIfExists('content_food');
        Schema::dropIfExists('content_photo');
        Schema::dropIfExists('content_paddle');
        Schema::dropIfExists('content_play_water');
        Schema::dropIfExists('content_hiking');
        Schema::dropIfExists('content_self_drive');
    }
};
