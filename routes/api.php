<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserFeedController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API v1 Routes
Route::prefix('v1')->group(function () {
    
    // Authentication endpoints (public)
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.login');
    
    // Public Articles endpoints
    Route::get('/articles', [ArticleController::class, 'index'])
        ->name('api.articles.index');

    // Authenticated User Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Personalized feed based on user preferences
        Route::get('/user/feed', [UserFeedController::class, 'index'])
            ->name('api.user.feed');
        
        // User endpoints
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.logout');
        
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.me');
        
        
    });
});

