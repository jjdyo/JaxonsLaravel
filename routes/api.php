<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExampleApiController;
use App\Http\Controllers\Api\SlackSlashController;
$host = parse_url(config('app.api_url'), PHP_URL_HOST);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Slack slash command endpoint (no auth for initial testing)
Route::post('/slack/slash', SlackSlashController::class);

/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
|
| Routes for testing functionality (should be removed in production)
|
*/
if (config('app.env') !== 'production') {
    Route::domain($host)->middleware('auth:sanctum')->group(function () {
            Route::get('/example/data', [ExampleApiController::class, 'getData']);
        });
}

