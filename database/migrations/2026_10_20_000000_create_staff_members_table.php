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
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 120)->nullable();
            // Posición en la que se muestra en la web (de menor a mayor).
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Foto opcional: file = archivo del gestor, image_url = imagen por link.
            $table->string('image_type', 20)->nullable();
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->timestamps();

            $table->index(['sort_order', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
