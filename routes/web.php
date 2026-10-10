<?php

use App\Http\Controllers\Admin\FileDownloadController;
use App\Http\Controllers\Admin\FileThumbnailController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Admin\BannerManager;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\CompanyForm;
use App\Livewire\Admin\CoverManager;
use App\Livewire\Admin\FileManager;
use App\Livewire\Admin\PopupManager;
use App\Livewire\Admin\ProjectForm;
use App\Livewire\Admin\ProjectIndex;
use App\Livewire\Admin\TestimonialManager;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/datos-cliente', CompanyForm::class)->name('client');
        Route::get('/banners', BannerManager::class)->name('banners');
        Route::get('/portadas', CoverManager::class)->name('covers');
        Route::get('/popup', PopupManager::class)->name('popup');
        Route::get('/proyectos', ProjectIndex::class)->name('projects');
        Route::get('/proyectos/crear', ProjectForm::class)->name('projects.create');
        Route::get('/proyectos/{project}/editar', ProjectForm::class)->name('projects.edit');
        Route::get('/categorias', CategoryManager::class)->name('categories');
        Route::get('/testimonios', TestimonialManager::class)->name('testimonials');
        Route::get('/archivos', FileManager::class)->name('files');
        Route::get('/archivos/{mediaFile}/descargar', FileDownloadController::class)->name('files.download');
        Route::get('/archivos/{mediaFile}/miniatura', FileThumbnailController::class)->name('files.thumbnail');
    });
});
