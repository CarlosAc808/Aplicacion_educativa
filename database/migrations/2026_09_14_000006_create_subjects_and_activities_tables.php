<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short', 40);
            $table->string('icon', 20)->default('✦');
            $table->string('color', 30)->default('violet');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('title');
            $table->text('hint')->nullable();
            $table->json('answers');
            $table->unsignedInteger('correct');
            $table->string('icon', 20)->default('☑');
            $table->string('format', 30)->default('Cuestionario');
            $table->string('format_type', 30)->default('quiz');
            $table->text('instruction')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('subjects');
    }
};