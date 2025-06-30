<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiTestController extends Controller
{
    public function apiTest()
{
    // 1. External API থেকে ডেটা নেয়া
    $response = Http::get('https://jsonplaceholder.typicode.com/todos');

    // 2. Response check করা
    if ($response->failed()) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch todos',
        ], 500);
    }

    // 3. Data process (প্রয়োজনে modify/limit/filter)
    $todos = $response->json(); // JSON ডেটা array তে রূপান্তর

    // Optional: শুধু প্রথম 10 টা todo return করতে চাইলে
    $todos = array_slice($todos, 0, 10);

    // 4. Return API response
    return response()->json([
        'status' => true,
        'message' => 'Todos fetched successfully',
        'data' => $todos
    ]);
}

}
