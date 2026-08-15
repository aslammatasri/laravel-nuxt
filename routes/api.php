<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Brand\ProductController as BrandProductController;
use App\Http\Controllers\Brand\VariationController;
use App\Http\Controllers\Creator\ProductController as CreatorProductController;
use App\Http\Controllers\Brand\ApplicationController as BrandApplicationController;
use App\Http\Controllers\Brand\CreatorController as BrandCreatorController;
use App\Http\Controllers\Creator\ApplicationController as CreatorApplicationController;
use App\Http\Controllers\Creator\SocialAccountController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Test route ─────────────────────────────────────────────────
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// ── Public routes (no auth needed) ────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',        [AuthController::class, 'register']);
    Route::post('login',           [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',  [AuthController::class, 'resetPassword']);
});

// ── Protected routes (must be logged in) ──────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // Profile (shared by both roles)
    Route::prefix('profile')->group(function () {
        Route::put('name',     [ProfileController::class, 'updateName']);
        Route::put('password', [ProfileController::class, 'updatePassword']);
    });

    // Notifications (shared by both roles)
    Route::prefix('notifications')->group(function () {
        Route::get('/',            [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('{id}/read',  [NotificationController::class, 'markAsRead']);
        Route::patch('read-all',   [NotificationController::class, 'markAllAsRead']);
    });

    // Brand only routes
    Route::middleware('role:brand')->prefix('brand')->group(function () {
        Route::apiResource('products', BrandProductController::class);
        Route::post('products/{product}/variations',               [VariationController::class, 'store']);
        Route::put('products/{product}/variations/{variation}',    [VariationController::class, 'update']);
        Route::delete('products/{product}/variations/{variation}', [VariationController::class, 'destroy']);
        Route::get('applications',                [BrandApplicationController::class, 'index']);
        Route::patch('applications/{id}/approve', [BrandApplicationController::class, 'approve']);
        Route::patch('applications/{id}/reject',  [BrandApplicationController::class, 'reject']);
        Route::get('creators/{id}/portfolio',     [BrandCreatorController::class, 'portfolio']);
    });

    // Creator only routes
    Route::middleware('role:creator')->prefix('creator')->group(function () {
        Route::get('marketplace',             [CreatorProductController::class, 'index']);
        Route::get('marketplace/categories',  [CreatorProductController::class, 'categories']);
        Route::get('marketplace/{id}',        [CreatorProductController::class, 'show']);
        Route::post('products/{id}/apply',    [CreatorApplicationController::class, 'store']);
        Route::get('applications',            [CreatorApplicationController::class, 'index']);
        Route::get('social-accounts',            [SocialAccountController::class, 'index']);
        Route::post('social-accounts/youtube',   [SocialAccountController::class, 'store']);
        Route::post('social-accounts/{id}/sync', [SocialAccountController::class, 'sync']);
        Route::delete('social-accounts/{id}',    [SocialAccountController::class, 'destroy']);
    });

});