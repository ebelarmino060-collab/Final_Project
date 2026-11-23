<?php

use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

// apiResource creates the standard REST routes; only() keeps just the ones we need now.
// GET /api/v1/tickets -> TicketController@index
Route::apiResource('tickets', TicketController::class)->only(['index']);
