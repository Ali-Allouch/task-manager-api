<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tasks', TaskController::class);

    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::apiResource('comments', CommentController::class)->only(['show', 'update', 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
