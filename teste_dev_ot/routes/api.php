<?php

use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\SearchContentController;
use App\Http\Controllers\SearchFileController;
use Illuminate\Support\Facades\Route;

Route::post('/upload', [FileUploadController::class, 'upload']);
Route::get('/search-file', [SearchFileController::class, 'search']);
Route::get('/search-content', [SearchContentController::class, 'search']);
