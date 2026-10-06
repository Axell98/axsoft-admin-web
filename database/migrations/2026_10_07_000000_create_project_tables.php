<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->longText('description')->nullable();
            $table->string('location', 150)->nullable();
            $table->string('category', 100)->nullable();
            $table->unsignedTinyInteger('execution_percentage')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            // Video opcional: file = archivo del gestor, youtube = video de YouTube.
            $table->string('video_type', 20)->nullable();
            $table->foreignId('video_media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('video_youtube_id', 20)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'created_at']);
        });

        // Cada imagen del proyecto representa una fase y lleva su propio título y contenido.
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // file = imagen del gestor de archivos, image_url = imagen por link.
            $table->string('type', 20);
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->string('title', 200)->nullable();
            $table->text('content')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_phases');
        Schema::dropIfExists('projects');
    }
};
