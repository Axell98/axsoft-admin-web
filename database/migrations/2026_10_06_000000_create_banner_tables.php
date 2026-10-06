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
        Schema::create('banner_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedTinyInteger('screen_percentage')->default(100);
            $table->boolean('show_arrows')->default(true);
            $table->boolean('show_indicators')->default(true);
            $table->timestamps();
        });

        Schema::create('banner_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_slider_id')->constrained('banner_sliders')->cascadeOnDelete();
            // file = archivo del gestor (imagen o video), image_url = imagen por link, youtube = video de YouTube.
            $table->string('type', 20);
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->cascadeOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->string('youtube_id', 20)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['banner_slider_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_slides');
        Schema::dropIfExists('banner_sliders');
    }
};
