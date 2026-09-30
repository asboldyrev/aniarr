<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\ReleaseDownloadController;
use App\Http\Controllers\Api\RssFeedController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TvdbSeriesSearchController;
use Illuminate\Support\Facades\Route;

// TheTVDB
Route::get('/tvdb/series/search', TvdbSeriesSearchController::class);

// Сериалы
Route::get('/series', [SeriesController::class, 'index']);
Route::get('/series/{series}', [SeriesController::class, 'show']);
Route::post('/series', [SeriesController::class, 'store']);
Route::patch('/series/{series}/monitoring', [SeriesController::class, 'updateMonitoring']);
Route::delete('/series/{series}', [SeriesController::class, 'destroy']);

// RSS
Route::post('/seasons/{season}/rss-feed', [RssFeedController::class, 'store']);
Route::patch('/rss-feeds/{rssFeed}', [RssFeedController::class, 'update']);
Route::delete('/rss-feeds/{rssFeed}', [RssFeedController::class, 'destroy']);

// Загрузки
Route::get('/downloads', [DownloadController::class, 'index']);
Route::get('/downloads/{download}', [DownloadController::class, 'show']);
Route::post('/downloads/{download}/cancel', [DownloadController::class, 'cancel']);
Route::post('/downloads/{download}/retry', [DownloadController::class, 'retry']);

// Настройки
Route::get('/settings', [SettingsController::class, 'index']);
Route::put('/settings', [SettingsController::class, 'update']);
Route::post('/settings/test/{service}', [SettingsController::class, 'test']);

// Релизы
Route::post('/releases/{release}/download', [ReleaseDownloadController::class, 'store']);

// Activity
Route::get('/activity', [ActivityLogController::class, 'index']);
Route::patch('/activity/{activityLog}/resolve', [ActivityLogController::class, 'resolve']);
Route::patch('/activity/{activityLog}/reopen', [ActivityLogController::class, 'reopen']);
