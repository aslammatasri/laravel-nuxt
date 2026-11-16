<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/r/{code}', \App\Http\Controllers\TrackedLinkController::class)->middleware('throttle:120,1');
