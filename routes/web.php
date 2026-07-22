<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\WebAuthController;
use Illuminate\Support\Facades\Route;

// 友好 404（/profile 等历史链接直接重定向到 /me 或首页）
Route::get('/profile', fn () => redirect('/me'));
Route::get('/me/routes', fn () => redirect('/me/contents')); // 兼容老链接

// 登录/注册/登出
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register']);
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// 主题切换（写到 session + localStorage；5 主题）
Route::post('/theme', function (\Illuminate\Http\Request $request) {
    $t = $request->input('theme', 'paper');
    if (! in_array($t, ['paper', 'sand', 'ink', 'mono', 'auto'], true)) {
        $t = 'paper';
    }
    $request->session()->put('theme', $t);
    return response()->json(['theme' => $t]);
})->name('theme.set');

Route::get('/', [HomeController::class, 'home'])->name('home');

// 8 大类列表
Route::get('/type/{key}', [HomeController::class, 'type'])->name('frontend.type');

// 内容贴 (8 大类合一)
Route::get('/content/{id}', [HomeController::class, 'contentShow'])->name('frontend.content');
// 兼容老链接
Route::get('/route/{id}', [HomeController::class, 'contentShow']);

// 地点 (location 子表)
Route::get('/place/{id}', [HomeController::class, 'place'])->name('frontend.place');

// 地图 / 雷达
Route::get('/map', [HomeController::class, 'map'])->name('frontend.map');
Route::get('/radar', [HomeController::class, 'radar'])->name('frontend.radar');

// 「我的」中心
Route::prefix('me')->group(function () {
    Route::get('/', [HomeController::class, 'me']);
    Route::get('/contents', [HomeController::class, 'myContents'])->name('frontend.my.contents');
    Route::get('/places', [HomeController::class, 'myPlaces'])->name('frontend.my.places');
    Route::get('/collections', [HomeController::class, 'myCollections'])->name('frontend.my.collections');
    Route::get('/activities', [HomeController::class, 'myActivities'])->name('frontend.my.activities');
});

// 评论 (多态)
Route::post('/{type}/{id}/comments', [HomeController::class, 'commentStore'])
    ->where('type', 'content|place|activity')
    ->name('frontend.comment.store');

// 活动
Route::get('/activities', [HomeController::class, 'activities'])->name('frontend.activities');
Route::get('/activities/create', [HomeController::class, 'activityCreate'])->name('frontend.activity.create');
Route::post('/activities', [HomeController::class, 'activityStore'])->name('frontend.activity.store');
Route::get('/activities/{id}', [HomeController::class, 'activityShow'])->name('frontend.activity');
Route::post('/activities/{id}/join', [HomeController::class, 'activityJoin'])->name('frontend.activity.join');
Route::post('/activities/{id}/leave', [HomeController::class, 'activityLeave'])->name('frontend.activity.leave');
