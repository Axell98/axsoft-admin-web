<?php

namespace App\Http\Controllers;

use App\Models\BannerSlide;
use App\Models\MediaFile;
use App\Models\Project;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'stats' => [
                ['label' => 'Banners', 'value' => BannerSlide::count(), 'icon' => 'image', 'route' => 'admin.banners'],
                ['label' => 'Proyectos visibles', 'value' => Project::where('is_published', true)->count(), 'icon' => 'layers', 'route' => 'admin.projects'],
                ['label' => 'Proyectos en total', 'value' => Project::count(), 'icon' => 'layers', 'route' => 'admin.projects'],
                ['label' => 'Archivos', 'value' => MediaFile::count(), 'icon' => 'folder', 'route' => 'admin.files'],
            ],
        ]);
    }
}
