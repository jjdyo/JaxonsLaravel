<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExampleApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

$host = parse_url(config('app.api_url'), PHP_URL_HOST);

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

