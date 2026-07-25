<?php

use App\Http\Controllers\Api\SeriesController;
use Illuminate\Support\Facades\Route;

// Сериалы
Route::post('/series', [SeriesController::class, 'store']);
