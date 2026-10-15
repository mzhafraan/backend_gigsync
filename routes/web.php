<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $request) {
    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'app' => 'GigSync Backend REST API',
            'status' => 'Online & Ready',
            'api_version' => 'v1',
            'base_url' => url('/api/v1'),
            'available_endpoints' => [
                'jobs' => url('/api/v1/jobs'),
                'categories' => url('/api/v1/categories'),
                'skills' => url('/api/v1/skills'),
            ]
        ]);
    }
    return view('test_auth');
});

Route::get('/test', function () {
    return view('test_auth');
});
