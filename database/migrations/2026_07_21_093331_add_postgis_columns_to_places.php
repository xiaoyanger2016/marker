<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostGIS 地理位置索引，用于雷达（附近点）查询的 ST_DWithin 加速
     * geography 类型自带 WGS84 球面计算，半径单位米
     */
    public function up(): void
    {
        // 添加 geography 字段，由经纬度自动计算
        DB::statement("
            ALTER TABLE places
            ADD COLUMN geog geography(Point, 4326)
            GENERATED ALWAYS AS (ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography) STORED
        ");

        DB::statement('CREATE INDEX places_geog_gix ON places USING GIST (geog)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS places_geog_gix');
        DB::statement('ALTER TABLE places DROP COLUMN IF EXISTS geog');
    }
};
