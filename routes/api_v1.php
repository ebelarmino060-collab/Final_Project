<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorsController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserTicketsController;
use Illuminate\Support\Facades\Route;

// Public routes: no token needed
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes: the auth:api middleware rejects requests without a valid token (401)
Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // GET /tickets, GET /tickets/{ticket}, POST /tickets and DELETE /tickets/{ticket}
    Route::apiResource('tickets', TicketController::class)->only(['index', 'show', 'store', 'destroy']);
    // PUT replaces the whole ticket (our own method name: replace)
    Route::put('tickets/{ticket}', [TicketController::class, 'replace']);
    // PATCH updates only the fields that were sent
    Route::patch('tickets/{ticket}', [TicketController::class, 'update']);

    // Authors are read only: list and show
    Route::apiResource('authors', AuthorsController::class)->only(['index', 'show']);

    // Tickets raised by one user: GET /users/{user}/tickets
    Route::apiResource('users.tickets', UserTicketsController::class)->only(['index']);
});
