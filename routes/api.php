<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('user')->group(function () {
    Route::post('login', [\App\Http\Controllers\UserController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\UserController::class, 'register']);

    Route::get('me', [\App\Http\Controllers\UserController::class, 'getUserInfo'])->middleware('jwt.auth.control');
    Route::post('logout', [\App\Http\Controllers\UserController::class, 'logout'])->middleware('jwt.auth.control');
});

Route::middleware('jwt.auth.control')->group(function () {

});
