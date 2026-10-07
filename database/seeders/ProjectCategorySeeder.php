<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectCategorySeeder extends Seeder
{
    /**
     * Categorías de proyectos del cliente. Se puede ejecutar varias veces sin duplicar.
     *
     * @var array<int, string>
     */
    private const CATEGORIES = ['Residencial', 'Oficina', 'Comercial'];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            ProjectCategory::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
