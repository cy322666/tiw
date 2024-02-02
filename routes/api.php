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

Route::post('tools/utms', [ToolsController::class, 'utms']);

Route::post('tools/marquiz', [ToolsController::class, 'marquiz']);

Route::post('tools/marquiz/cron', [ToolsController::class, 'cron']);

Route::get('tools/task', [ToolsController::class, 'task']);

Route::post('tg/link', [TgController::class, 'link']);

Route::post('tg/hook', [TgController::class, 'hook']);

Route::get('tg/redirect', [TgController::class, 'redirect']);

Route::post('tg/shipment', [TgController::class, 'shipment']);

Route::post('tg/constructor', [TgController::class, 'constructor']);

Route::post('tg/quiz', [TgController::class, 'quiz']);

