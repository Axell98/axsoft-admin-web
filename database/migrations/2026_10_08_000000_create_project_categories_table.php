<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_category_id')->nullable()->after('location')->constrained('project_categories')->nullOnDelete();
        });

        // Las categorías que ya estaban escritas a mano pasan a la nueva tabla.
        $slugs = [];

        foreach (DB::table('projects')->whereNotNull('category')->distinct()->pluck('category') as $original) {
            $name = trim((string) $original);
            $slug = Str::slug($name);

            if ($name === '' || $slug === '') {
                continue;
            }

            if (! isset($slugs[$slug])) {
                $slugs[$slug] = DB::table('project_categories')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('projects')->where('category', $original)->update(['project_category_id' => $slugs[$slug]]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('location');
        });

        foreach (DB::table('project_categories')->get() as $category) {
            DB::table('projects')->where('project_category_id', $category->id)->update(['category' => $category->name]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_category_id');
        });

        Schema::dropIfExists('project_categories');
    }
};
