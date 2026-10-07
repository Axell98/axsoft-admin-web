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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 120)->nullable();
            $table->text('content');
            // Imagen opcional: file = archivo del gestor, image_url = imagen por link.
            $table->string('image_type', 20)->nullable();
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
