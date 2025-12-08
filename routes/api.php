<?php

use App\Http\Controllers\Api\GroupApiController;
use App\Http\Controllers\Api\NoteApiController;
use App\Http\Controllers\Api\ShareApiController;
use App\Http\Controllers\Api\TagApiController;
use App\Http\Controllers\Api\UserApiController;
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
    Route::get('/notes/with-groups', [NoteApiController::class, 'withGroups']);
    Route::get('/notes/{note}', [NoteApiController::class, 'show']);
    Route::post('/notes/{note}/decrypt', [NoteApiController::class, 'decrypt']);
    Route::post('/notes/{note}/toggle-pin', [NoteApiController::class, 'togglePin']);
    Route::post('/notes/{note}/toggle-favorite', [NoteApiController::class, 'toggleFavorite']);
    Route::patch('/notes/{note}/archive', [NoteApiController::class, 'archive']);
    Route::patch('/notes/{note}/color', [NoteApiController::class, 'updateColor']);
    Route::patch('/notes/{note}/tags', [NoteApiController::class, 'updateTags']);
    Route::post('/notes/{note}/add-to-group', [NoteApiController::class, 'addToGroup']);
    Route::post('/notes/{note}/remove-from-group', [NoteApiController::class, 'removeFromGroup']);
    Route::post('/notes/{note}/replicate', [NoteApiController::class, 'replicate']);
    Route::post('/notes/{note}/open', [NoteApiController::class, 'incrementOpenCount']);
    Route::post('/notes/create-group', [NoteApiController::class, 'createGroupFromNotes']);

    // Tags API
    Route::get('/tags', [TagApiController::class, 'index']);
    Route::post('/tags', [TagApiController::class, 'store']);
    Route::put('/tags/{tag}', [TagApiController::class, 'update']);
    Route::delete('/tags/{tag}', [TagApiController::class, 'destroy']);

    // Groups API
    Route::get('/groups', [GroupApiController::class, 'index']);
    Route::post('/groups', [GroupApiController::class, 'store']);
    Route::post('/groups/merge', [GroupApiController::class, 'merge']);
    Route::put('/groups/{group}', [GroupApiController::class, 'update']);
    Route::delete('/groups/{group}', [GroupApiController::class, 'destroy']);

    // Shares API
    Route::get('/shared/with-me', [ShareApiController::class, 'sharedWithMe']);
    Route::get('/shared/by-me', [ShareApiController::class, 'sharedByMe']);
    Route::get('/shared/with-me/notes', [ShareApiController::class, 'sharedWithMeNotes']);
    Route::get('/shared/by-me/notes', [ShareApiController::class, 'sharedByMeNotes']);
    Route::get('/notes/{note}/shares', [ShareApiController::class, 'getNoteShares']);
    Route::post('/shares', [ShareApiController::class, 'store']);
    Route::delete('/shares/{share}', [ShareApiController::class, 'destroy']);

    // User Preferences API
    Route::get('/user/preferences', [UserApiController::class, 'preferences']);
    Route::put('/user/preferences', [UserApiController::class, 'updatePreferences']);
});
