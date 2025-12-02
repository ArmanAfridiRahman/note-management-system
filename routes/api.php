<?php

use App\Http\Controllers\Api\GroupApiController;
use App\Http\Controllers\Api\NoteApiController;
use App\Http\Controllers\Api\ShareApiController;
use App\Http\Controllers\Api\TagApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API routes for AJAX calls (authenticated via Sanctum session)
Route::middleware('auth:sanctum')->group(function () {
    // Notes API
    Route::get('/notes', [NoteApiController::class, 'index']);
    Route::get('/notes/search', [NoteApiController::class, 'search']);
    Route::get('/notes/{note}', [NoteApiController::class, 'show']);
    Route::post('/notes/{note}/decrypt', [NoteApiController::class, 'decrypt']);
    Route::patch('/notes/{note}/pin', [NoteApiController::class, 'togglePin']);
    Route::patch('/notes/{note}/favorite', [NoteApiController::class, 'toggleFavorite']);
    Route::patch('/notes/{note}/archive', [NoteApiController::class, 'archive']);

    // Tags API
    Route::get('/tags', [TagApiController::class, 'index']);
    Route::post('/tags', [TagApiController::class, 'store']);
    Route::put('/tags/{tag}', [TagApiController::class, 'update']);
    Route::delete('/tags/{tag}', [TagApiController::class, 'destroy']);

    // Groups API
    Route::get('/groups', [GroupApiController::class, 'index']);
    Route::post('/groups', [GroupApiController::class, 'store']);
    Route::put('/groups/{group}', [GroupApiController::class, 'update']);
    Route::delete('/groups/{group}', [GroupApiController::class, 'destroy']);

    // Shares API
    Route::get('/shared/with-me', [ShareApiController::class, 'sharedWithMe']);
    Route::get('/shared/by-me', [ShareApiController::class, 'sharedByMe']);
    Route::post('/shares', [ShareApiController::class, 'store']);
    Route::delete('/shares/{share}', [ShareApiController::class, 'destroy']);
});
