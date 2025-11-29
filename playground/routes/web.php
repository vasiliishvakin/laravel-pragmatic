<?php

use App\Http\Controllers\SandboxController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sandbox', [SandboxController::class, 'index']);
