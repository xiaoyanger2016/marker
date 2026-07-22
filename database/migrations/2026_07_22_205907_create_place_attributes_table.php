<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8 大类 type-specific 属性 (key-value 关联表)
 *
 * 一个 place 的属性因 type 不同而差异巨大：
 *   - 自驾线路: 距离/海拔/路况/加油站/途经点
 *   - 玩水点: 水深/可游泳/救生员
 *   - 徒步线路: 距离/难度/装备清单
 *   - 拍照点: 最佳时间/机位/光影
 *   - 露营点: 海拔/有水/有厕所/可明火
 *   - 日出日落: 最佳时间/方位/机位
 *   - 美食: 人均/营业时间/招牌菜
 *   - 桨板: 水深/水流/装备租赁
 *
 * 用 key-value 关联表 (而不是 EAV 列)：
 *   + 不用为每种 type 加新表
 *   + 增/删/改属性不动 schema
 *   + 可以 query / sort / filter
 *   + 系统预定义 display_label 让 admin 自动生成 input
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();

            // key: distance_km / altitude_meters / difficulty / gear_checklist ...
            $table->string('attribute_key', 60);

            // value: 字符串/JSON/数字/布尔都靠 value_type 解析
            $table->text('attribute_value')->nullable();

            // 解析类型: string / int / float / bool / json / array
            $table->string('value_type', 20)->default('string');

            // 系统预定义: 给 admin form 自动生成对应 input
            // 自定义: 用户手动加的 (sort=999)
            $table->boolean('is_system')->default(true);

            // 展示配置 (admin 渲染时用)
            $table->string('display_label', 60)->nullable();       // 距离 (km)
            $table->string('display_group', 30)->nullable();       // 基本信息 / 装备 / 安全
            $table->string('input_type', 20)->default('text');    // text / number / toggle / textarea / repeater
            $table->string('unit', 20)->nullable();               // km / m / 分钟 / 元
            $table->unsignedSmallInteger('sort')->default(0);

            $table->timestamps();

            $table->unique(['place_id', 'attribute_key']);
            $table->index(['attribute_key', 'value_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_attributes');
    }
};
