<?php

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// TEMPORARY: delete in Lesson 7. Returns every ticket so we can see the seeded data.
Route::get('/tickets', function () {
    return Ticket::all();
});
