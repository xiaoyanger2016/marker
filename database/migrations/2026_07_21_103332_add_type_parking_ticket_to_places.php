<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // 地点细类（在分类 category 之下，更具体的 POI 类型）
            $table->string('place_type', 60)->nullable()->after('category_id');
            $table->index('place_type');

            // 停车
            $table->boolean('has_parking')->default(false);
            $table->string('parking_fee_type', 20)->nullable(); // free / per_time / per_hour / per_day / unknown
            $table->decimal('parking_fee', 8, 2)->nullable();
            $table->text('parking_notes')->nullable();
            $table->unsignedInteger('parking_capacity')->nullable(); // 大约车位

            // 门票
            $table->boolean('has_ticket')->default(false);
            $table->decimal('ticket_price', 8, 2)->nullable();
            $table->string('ticket_unit', 20)->default('人'); // 人 / 车 / 次
            $table->text('ticket_notes')->nullable();

            // 适合人群/季节（标签化）
            $table->string('best_season', 100)->nullable(); // 春夏秋冬/四季/节假日
            $table->string('suitable_for', 200)->nullable(); // 亲子/情侣/朋友/独自
            $table->unsignedInteger('recommended_duration_minutes')->nullable(); // 建议游玩时长（分钟）

            // 难度/强度
            $table->string('difficulty', 20)->nullable(); // easy / moderate / hard
            $table->unsignedInteger('altitude_meters')->nullable(); // 海拔（米）

            // 装备/注意事项
            $table->jsonb('gear_checklist')->nullable(); // ["帐篷", "睡袋", "防潮垫"]
            $table->jsonb('safety_notes')->nullable(); // ["注意防晒", "禁止明火"]

            // 联系/预订
            $table->string('booking_url')->nullable();
            $table->string('wechat_id')->nullable();

            $table->index('has_parking');
            $table->index('has_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['has_parking']);
            $table->dropIndex(['has_ticket']);
            $table->dropIndex(['place_type']);
            $table->dropColumn([
                'place_type',
                'has_parking', 'parking_fee_type', 'parking_fee', 'parking_notes', 'parking_capacity',
                'has_ticket', 'ticket_price', 'ticket_unit', 'ticket_notes',
                'best_season', 'suitable_for', 'recommended_duration_minutes',
                'difficulty', 'altitude_meters',
                'gear_checklist', 'safety_notes',
                'booking_url', 'wechat_id',
            ]);
        });
    }
};
