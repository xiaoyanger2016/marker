<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'joined', 'rejected', 'cancelled'])->default('joined');
            $table->unsignedTinyInteger('people_count')->default(1)->comment('报名几人');
            $table->text('note')->nullable()->comment('备注/留言');
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
    }
};
