<?php

use App\Http\Controllers\Api\AmapController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| 3 端共享：uni-app 前台 + Vue Admin + Filament 数据中台
|
| 读操作（GET）支持游客访问，但 controller 会根据 token 智能返回：
|   - 无 token: 仅 is_public=true
|   - 有 token: 自己全部 + 别人的 is_public=true
*/

// === 公开：分享、健康检查、注册登录 ===
Route::prefix('v1')->group(function () {
    Route::get('share/place/{token}', [ShareController::class, 'showPlace']);
    Route::get('share/collection/{token}', [ShareController::class, 'showCollection']);

    Route::get('health', fn () => response()->json([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
        'version' => '1.0.0',
    ]));

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});

// === 公开可读（GET）===
Route::prefix('v1')->group(function () {
    Route::get('places', [PlaceController::class, 'index']);
    Route::get('places/nearby', [PlaceController::class, 'nearby']);
    Route::get('places/{id}', [PlaceController::class, 'show']);

    Route::get('routes', [RouteController::class, 'index']);
    Route::get('routes/{id}', [RouteController::class, 'show']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);

    // 高德 POI 搜索（用于"导入"功能，无需登录）
    Route::get('amap/text', [AmapController::class, 'textSearch']);
    Route::get('amap/geocode', [AmapController::class, 'geocode']);
});

// === 必须登录 ===
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // 认证
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);

    // 写操作
    Route::post('categories', [CategoryController::class, 'store']);
    Route::post('tags', [TagController::class, 'store']);

    // Place 写
    Route::post('places', [PlaceController::class, 'store']);
    Route::put('places/{id}', [PlaceController::class, 'update']);
    Route::patch('places/{id}', [PlaceController::class, 'update']);
    Route::delete('places/{id}', [PlaceController::class, 'destroy']);
    Route::post('places/{id}/media', [PlaceController::class, 'uploadMedia']);
    Route::delete('places/{id}/media/{media}', [PlaceController::class, 'deleteMedia']);

    // Route 写
    Route::post('routes', [RouteController::class, 'store']);
    Route::put('routes/{id}', [RouteController::class, 'update']);
    Route::patch('routes/{id}', [RouteController::class, 'update']);
    Route::delete('routes/{id}', [RouteController::class, 'destroy']);
    Route::post('routes/{id}/like', [RouteController::class, 'like']);

    // 收藏集
    Route::get('collections', [CollectionController::class, 'index']);
    Route::post('collections', [CollectionController::class, 'store']);
    Route::get('collections/{id}', [CollectionController::class, 'show']);
    Route::put('collections/{id}', [CollectionController::class, 'update']);
    Route::patch('collections/{id}', [CollectionController::class, 'update']);
    Route::delete('collections/{id}', [CollectionController::class, 'destroy']);
    Route::post('collections/{id}/places/{placeId}', [CollectionController::class, 'attachPlace']);
    Route::delete('collections/{id}/places/{placeId}', [CollectionController::class, 'detachPlace']);

    // 笔记
    Route::get('notes', [NoteController::class, 'index']);
    Route::post('notes', [NoteController::class, 'store']);
    Route::get('notes/{id}', [NoteController::class, 'show']);
    Route::put('notes/{id}', [NoteController::class, 'update']);
    Route::patch('notes/{id}', [NoteController::class, 'update']);
    Route::delete('notes/{id}', [NoteController::class, 'destroy']);

    // 高德 API 代理（around 需要定位，所以要 auth）
    Route::get('amap/around', [AmapController::class, 'aroundSearch']);
});
