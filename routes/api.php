<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Brand\ProductController as BrandProductController;
use App\Http\Controllers\Brand\VerificationController as BrandVerificationController;
use App\Http\Controllers\Brand\VariationController;
use App\Http\Controllers\Creator\ProductController as CreatorProductController;
use App\Http\Controllers\Brand\ApplicationController as BrandApplicationController;
use App\Http\Controllers\Brand\CreatorController as BrandCreatorController;
use App\Http\Controllers\Brand\DashboardController as BrandDashboardController;
use App\Http\Controllers\Creator\ApplicationController as CreatorApplicationController;
use App\Http\Controllers\Creator\CollaborationController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\SocialAccountController;
use App\Http\Controllers\ConversationController;
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
        Route::post('avatar',   [ProfileController::class, 'updateAvatar']);
        Route::delete('avatar', [ProfileController::class, 'deleteAvatar']);
        Route::put('password', [ProfileController::class, 'updatePassword']);
    });

    // Notifications (shared by both roles)
    Route::prefix('notifications')->group(function () {
        Route::get('/',            [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('{id}/read',  [NotificationController::class, 'markAsRead']);
        Route::patch('read-all',   [NotificationController::class, 'markAllAsRead']);
    });

    // Messages (shared by both roles)
    Route::prefix('conversations')->group(function () {
        Route::get('/',                 [ConversationController::class, 'index']);
        Route::post('/',                [ConversationController::class, 'store']);
        Route::get('unread-count',      [ConversationController::class, 'unreadCount']);
        Route::get('{id}/messages',     [ConversationController::class, 'messages']);
        Route::post('{id}/messages',    [ConversationController::class, 'send']);
        Route::patch('{id}/read',       [ConversationController::class, 'markRead']);
    });

    // Brand only routes
    Route::middleware('role:brand')->prefix('brand')->group(function () {
        Route::get('dashboard/stats', [BrandDashboardController::class, 'stats']);
        Route::get('verification',    [BrandVerificationController::class, 'show']);
        Route::put('verification',    [BrandVerificationController::class, 'update']);
        Route::apiResource('products', BrandProductController::class);
        Route::post('products/{product}/variations',               [VariationController::class, 'store']);
        Route::put('products/{product}/variations/{variation}',    [VariationController::class, 'update']);
        Route::delete('products/{product}/variations/{variation}', [VariationController::class, 'destroy']);
        Route::get('applications',                [BrandApplicationController::class, 'index']);
        Route::patch('applications/{id}/approve', [BrandApplicationController::class, 'approve']);
        Route::patch('applications/{id}/reject',  [BrandApplicationController::class, 'reject']);
        Route::get('creators/{id}/portfolio',     [BrandCreatorController::class, 'portfolio']);
    });

    // Admin only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('stats',                       [AdminStatsController::class, 'index']);
        Route::get('users',                       [AdminUserController::class, 'index']);
        Route::patch('users/{id}/suspend',        [AdminUserController::class, 'suspend']);
        Route::patch('users/{id}/reactivate',     [AdminUserController::class, 'reactivate']);
        Route::get('brands',                [AdminBrandController::class, 'index']);
        Route::patch('brands/{id}/verify',  [AdminBrandController::class, 'verify']);
        Route::patch('brands/{id}/reject',  [AdminBrandController::class, 'reject']);
    });

    // Creator only routes
    Route::middleware('role:creator')->prefix('creator')->group(function () {
        Route::get('dashboard/stats',         [CreatorDashboardController::class, 'stats']);
        Route::get('marketplace',             [CreatorProductController::class, 'index']);
        Route::get('marketplace/categories',  [CreatorProductController::class, 'categories']);
        Route::get('marketplace/{id}',        [CreatorProductController::class, 'show']);
        Route::post('products/{id}/apply',    [CreatorApplicationController::class, 'store']);
        Route::get('applications',            [CreatorApplicationController::class, 'index']);
        Route::get('collaborations',          [CollaborationController::class, 'index']);
        Route::get('social-accounts',            [SocialAccountController::class, 'index']);
        Route::post('social-accounts/youtube',   [SocialAccountController::class, 'store']);
        Route::post('social-accounts/{id}/sync', [SocialAccountController::class, 'sync']);
        Route::delete('social-accounts/{id}',    [SocialAccountController::class, 'destroy']);
    });

});