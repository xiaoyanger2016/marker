<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_place', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->text('note')->nullable(); // 加入此收藏集时的小笔记
            $table->timestamps();

            $table->unique(['collection_id', 'place_id']);
            $table->index(['collection_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_place');
    }
};
