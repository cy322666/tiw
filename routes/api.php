<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\TgController;
use App\Http\Controllers\ToolsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('site/quiz', [SiteController::class, 'quiz']);

Route::post('site/web', [SiteController::class, 'web']);

Route::post('tools/active', [ToolsController::class, 'active']);

Route::post('tg/link', [TgController::class, 'link']);

Route::post('tg/hook', [TgController::class, 'hook']);

Route::get('tg/redirect', [TgController::class, 'redirect']);

