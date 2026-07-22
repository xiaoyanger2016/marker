<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('role', 30)->default('reference'); // reference | inspiration | detailed
            $table->timestamps();

            $table->unique(['content_id', 'note_id']);
            $table->index(['content_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_notes');
    }
};
