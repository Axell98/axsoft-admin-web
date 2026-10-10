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
        Schema::create('page_covers', function (Blueprint $table) {
            $table->id();
            // Clave de la página interna (ver config/covers.php). Una portada por página.
            $table->string('page', 60)->unique();
            $table->string('title', 150)->nullable();
            // file = imagen del gestor de archivos, image_url = imagen por link.
            $table->string('image_type', 20)->nullable();
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_covers');
    }
};
