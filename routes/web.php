<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test\TestController;

Route::get('/', function () {
    return view('welcome');
});



Route::get('test', [TestController::class, 'index']);

// Include health check routes
require __DIR__.'/health.php';
