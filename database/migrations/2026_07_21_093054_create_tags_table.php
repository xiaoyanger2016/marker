<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('slug', 60);
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
