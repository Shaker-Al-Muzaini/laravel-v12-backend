<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('test', App\Http\Controllers\Test\TestController::class,"index");

// Include health check routes
require __DIR__.'/health.php';
