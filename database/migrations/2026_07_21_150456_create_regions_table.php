<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('行政区划代码（自定义短码）');
            $table->string('name', 50)->comment('名称');
            $table->enum('level', ['country', 'province', 'city'])->comment('层级');
            $table->string('parent_code', 20)->nullable()->index();
            $table->string('pinyin', 100)->nullable()->comment('拼音');
            $table->string('short_name', 20)->nullable()->comment('简称');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_hot')->default(false)->comment('热门城市');
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->index(['level', 'parent_code']);
            $table->index(['name', 'pinyin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
