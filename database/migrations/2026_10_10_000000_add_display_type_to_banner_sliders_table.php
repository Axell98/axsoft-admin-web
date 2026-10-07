<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banner_sliders', function (Blueprint $table) {
            // slider = imágenes que se pasan en carrusel, video = un solo video.
            $table->string('display_type', 20)->default('slider')->after('name');
        });

        // Un slider que ya solo tenía videos pasa a ser de tipo video.
        $videoExtensions = config('files.types.video', []);

        foreach (DB::table('banner_sliders')->pluck('id') as $sliderId) {
            $slides = DB::table('banner_slides')
                ->leftJoin('media_files', 'media_files.id', '=', 'banner_slides.media_file_id')
                ->where('banner_slides.banner_slider_id', $sliderId)
                ->get(['banner_slides.type', 'media_files.extension']);

            $onlyVideos = $slides->isNotEmpty() && $slides->every(
                fn ($slide) => $slide->type === 'youtube' || ($slide->type === 'file' && in_array($slide->extension, $videoExtensions, true)),
            );

            if ($onlyVideos) {
                DB::table('banner_sliders')->where('id', $sliderId)->update(['display_type' => 'video']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_sliders', function (Blueprint $table) {
            $table->dropColumn('display_type');
        });
    }
};
