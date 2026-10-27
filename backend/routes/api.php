<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OccurrenceController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:password');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [ProfileController::class, 'show']);
    Route::patch('me', [ProfileController::class, 'update']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->whereUuid('category');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->whereUuid('category');

    Route::get('occurrences', [OccurrenceController::class, 'index']);
    Route::get('stats/days', [StatsController::class, 'days']);

    Route::post('tasks', [TaskController::class, 'store']);
    Route::get('tasks/{task}', [TaskController::class, 'show'])->whereUuid('task');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->whereUuid('task');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->whereUuid('task');
    Route::put('tasks/{task}/occurrences/{date}', [OccurrenceController::class, 'update'])
        ->whereUuid('task')
        ->where('date', '\d{4}-\d{2}-\d{2}');
});
