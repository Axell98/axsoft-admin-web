<?php

use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PopupController;
use App\Http\Controllers\Api\ProjectCategoryController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API pública (solo lectura)
|--------------------------------------------------------------------------
|
| Consumida por el sitio web del cliente. No requiere autenticación,
| por lo que solo debe exponer información que ya es pública.
|
*/

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/company', [CompanyController::class, 'show'])->name('api.company');
    Route::get('/banners', [BannerController::class, 'show'])->name('api.banners');
    Route::get('/project-categories', [ProjectCategoryController::class, 'index'])->name('api.project-categories');
    Route::get('/popup', [PopupController::class, 'show'])->name('api.popup');
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('api.testimonials');
    Route::get('/projects', [ProjectController::class, 'index'])->name('api.projects');
    Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('api.projects.show');
});
