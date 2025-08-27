<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExampleApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Example API routes - protected by Sanctum and using the api_url configuration
$host = parse_url(config('app.api_url'), PHP_URL_HOST);
Route::domain($host)->middleware('auth:sanctum')->group(function () {
    // Example route for getting data
    Route::get('/example/data', [ExampleApiController::class, 'getData']);
});
