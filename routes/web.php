<?php

use App\Http\Controllers\Frontend\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');

Route::get('/type/{key}', [HomeController::class, 'type'])->name('frontend.type');
Route::get('/place/{id}', [HomeController::class, 'place'])->name('frontend.place');
Route::get('/route/{id}', [HomeController::class, 'routeShow'])->name('frontend.route');
Route::get('/map', [HomeController::class, 'map'])->name('frontend.map');
Route::get('/radar', [HomeController::class, 'radar'])->name('frontend.radar');
