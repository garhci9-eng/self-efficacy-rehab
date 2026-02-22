<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\EfficacyController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WishTreeController;
use App\Http\Controllers\Api\DiaryController;

Route::prefix('v1')->group(function () {

    // ── 환자 ──────────────────────────────────────────
    Route::get('/patients',           [PatientController::class, 'index']);
    Route::post('/patients',          [PatientController::class, 'store']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);

    // ── 활동 ──────────────────────────────────────────
    Route::get('/activities', [ActivityController::class, 'index']);

    Route::prefix('patients/{patient}')->group(function () {
        Route::get('/activities/today',              [ActivityController::class, 'today']);
        Route::get('/activities/history',            [ActivityController::class, 'history']);
        Route::post('/activities/{activity}/complete', [ActivityController::class, 'complete']);

        // 자기효능감 평가
        Route::get('/efficacy/history',    [EfficacyController::class, 'history']);
        Route::post('/efficacy',           [EfficacyController::class, 'store']);

        // AI 채팅
        Route::get('/chat/history',  [ChatController::class, 'history']);
        Route::post('/chat',         [ChatController::class, 'chat']);

        // 소원 나무
        Route::get('/wishes',               [WishTreeController::class, 'index']);
        Route::post('/wishes',              [WishTreeController::class, 'store']);
        Route::delete('/wishes/{wish}',     [WishTreeController::class, 'destroy']);

        // 구술 일기
        Route::get('/diary',    [DiaryController::class, 'index']);
        Route::post('/diary',   [DiaryController::class, 'store']);
    });

    // 자기효능감 문항
    Route::get('/efficacy/questions', [EfficacyController::class, 'questions']);
});
