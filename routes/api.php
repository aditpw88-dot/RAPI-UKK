<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RoomController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/password/forgot', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:6,1');
Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:6,1');
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/events', [EventController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::patch('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

    Route::get('/events/mine', [EventController::class, 'mine']);
    Route::get('/events/pending', [EventController::class, 'pending']);
    Route::get('/events/managed', [EventController::class, 'managed']);
    Route::post('/events', [EventController::class, 'store']);
    Route::post('/events/{event}/approve', [EventController::class, 'approve']);
    Route::post('/events/{event}/reject', [EventController::class, 'reject']);

    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);
    Route::get('/pic/reports/complaints/pdf', [\App\Http\Controllers\PicComplaintReportController::class, 'exportApiPdf']);
});