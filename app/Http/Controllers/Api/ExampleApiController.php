<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class ExampleApiController extends Controller
{
    /**
     * Get example data for the authenticated user
     *
     * @param Request $request The request object
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\AuthenticationException If user is not authenticated
     */
    public function getData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

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
