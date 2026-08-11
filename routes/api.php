<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\ReleaseDownloadController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\TvdbSeriesSearchController;
use Illuminate\Support\Facades\Route;

// TheTVDB
Route::get('/tvdb/series/search', TvdbSeriesSearchController::class);

// Сериалы
Route::get('/series', [SeriesController::class, 'index']);
Route::get('/series/{series}', [SeriesController::class, 'show']);
Route::post('/series', [SeriesController::class, 'store']);

// Загрузки
Route::get('/downloads', [DownloadController::class, 'index']);
Route::get('/downloads/{download}', [DownloadController::class, 'show']);

// Релизы
Route::post('/releases/{release}/download', [ReleaseDownloadController::class, 'store']);

// Activity
Route::get('/activity', [ActivityLogController::class, 'index']);
Route::patch('/activity/{activityLog}/resolve', [ActivityLogController::class, 'resolve']);
Route::patch('/activity/{activityLog}/reopen', [ActivityLogController::class, 'reopen']);
