<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ReleaseDownloadController;
use App\Http\Controllers\Api\SeriesController;
use Illuminate\Support\Facades\Route;

// Сериалы
Route::get('/series', [SeriesController::class, 'index']);
Route::get('/series/{series}', [SeriesController::class, 'show']);
Route::post('/series', [SeriesController::class, 'store']);

// Релизы
Route::post('/releases/{release}/download', [ReleaseDownloadController::class, 'store']);

// Activity
Route::get('/activity', [ActivityLogController::class, 'index']);
Route::patch('/activity/{activityLog}/resolve', [ActivityLogController::class, 'resolve']);
Route::patch('/activity/{activityLog}/reopen', [ActivityLogController::class, 'reopen']);
