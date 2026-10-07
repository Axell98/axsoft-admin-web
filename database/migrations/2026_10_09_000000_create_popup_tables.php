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
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->nullable();
            // image = una imagen, slider = varias imágenes, video = un video.
            $table->string('display_type', 20)->default('image');
            $table->boolean('is_visible')->default(false);
            $table->boolean('show_header')->default(false);
            $table->boolean('show_border')->default(false);
            $table->string('link_url', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('popup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_id')->constrained('popups')->cascadeOnDelete();
            // file = archivo del gestor (imagen o video), image_url = imagen por link, youtube = video de YouTube.
            $table->string('type', 20);
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->cascadeOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->string('youtube_id', 20)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['popup_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popup_items');
        Schema::dropIfExists('popups');
    }
};
