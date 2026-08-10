<?php

use App\Http\Controllers\Api\ReleaseDownloadController;
use App\Http\Controllers\Api\SeriesController;
use Illuminate\Support\Facades\Route;

// Сериалы
Route::post('/series', [SeriesController::class, 'store']);

// Релизы
Route::post('/releases/{release}/download', [ReleaseDownloadController::class, 'store']);
