<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExampleApiController extends Controller
{
    /**
     * Get example data for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        $user = $request->user();

        // Example data to return
        $data = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'example_data' => [
                'message' => 'This is example data from the API',
                'timestamp' => now()->toIso8601String(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

}
