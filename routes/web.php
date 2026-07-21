<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\WebAuthController;
use Illuminate\Support\Facades\Route;

// 友好 404（/profile 等历史链接直接重定向到 /me 或首页）
Route::get('/profile', fn () => redirect('/me'));

// 登录/注册/登出
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register']);
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// 主题切换（写到 session + localStorage）
Route::post('/theme', function (\Illuminate\Http\Request $request) {
    $t = $request->input('theme', 'paper');
    if (! in_array($t, ['paper', 'ink', 'mono'], true)) {
        $t = 'paper';
    }
    $request->session()->put('theme', $t);
    return response()->json(['theme' => $t]);
})->name('theme.set');

Route::get('/', [HomeController::class, 'home'])->name('home');

Route::get('/type/{key}', [HomeController::class, 'type'])->name('frontend.type');
Route::get('/place/{id}', [HomeController::class, 'place'])->name('frontend.place');
Route::get('/route/{id}', [HomeController::class, 'routeShow'])->name('frontend.route');
Route::get('/map', [HomeController::class, 'map'])->name('frontend.map');
Route::get('/radar', [HomeController::class, 'radar'])->name('frontend.radar');

// 「我的」中心
Route::prefix('me')->group(function () {
    Route::get('/', [HomeController::class, 'me']);
    Route::get('/places', [HomeController::class, 'myPlaces']);
    Route::get('/routes', [HomeController::class, 'myRoutes']);
    Route::get('/collections', [HomeController::class, 'myCollections']);
    Route::get('/activities', [HomeController::class, 'myActivities']);
});

// 活动（占位，Phase 4 实现后改回 HomeController）
Route::get('/activities', [HomeController::class, 'activities'])->name('frontend.activities');
Route::get('/activities/create', [HomeController::class, 'activityCreate'])->name('frontend.activity.create');
Route::post('/activities', [HomeController::class, 'activityStore'])->name('frontend.activity.store');
Route::get('/activities/{id}', [HomeController::class, 'activityShow'])->name('frontend.activity');
Route::post('/activities/{id}/join', [HomeController::class, 'activityJoin'])->name('frontend.activity.join');
Route::post('/activities/{id}/leave', [HomeController::class, 'activityLeave'])->name('frontend.activity.leave');
