<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('name', 200);
            $table->string('slug', 200)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 60)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('country', 60)->default('中国');
            $table->string('district', 60)->nullable(); // 区/县

            // 经纬度（高精度）
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('business_hours')->nullable(); // 营业时间字符串
            $table->decimal('price_range', 8, 2)->nullable(); // 人均消费
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5 星
            $table->date('visited_at')->nullable(); // 上次去过时间
            $table->unsignedInteger('visit_count')->default(0); // 总共去过几次
            $table->boolean('is_visited')->default(false);
            $table->boolean('is_wishlist')->default(false); // 想去的（种草）
            $table->boolean('is_public')->default(true); // 是否对外公开（可分享）

            // POI 来源
            $table->string('poi_source', 30)->nullable(); // amap / baidu / xiaohongshu / manual
            $table->string('poi_id', 200)->nullable(); // 第三方 ID
            $table->string('poi_type', 100)->nullable(); // 高德分类（如 "餐饮服务;中餐厅"）

            $table->jsonb('metadata')->nullable(); // 灵活扩展

            $table->timestamps();
            $table->softDeletes();

            // 基础索引
            $table->index(['user_id', 'is_public']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'is_wishlist']);
            $table->index(['user_id', 'is_visited']);
            $table->index(['city', 'province']);
            $table->index(['poi_source', 'poi_id']);
            $table->index('created_at');

            // 经纬度索引（用于附近点查询，PG 上 btree 即可，PostGIS 用 GIST）
            $table->index(['latitude', 'longitude']);
        });

        // PostGIS 地理位置字段（雷达模式用 ST_DWithin 加速）
        // 用一个独立 migration 调用 raw SQL
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
