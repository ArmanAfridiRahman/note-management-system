<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SharedController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notes
    Route::get('/notes/archived', [NoteController::class, 'archived'])->name('notes.archived');
    Route::get('/notes/favorites', [NoteController::class, 'favorites'])->name('notes.favorites');
    Route::get('/notes/encrypted', [NoteController::class, 'encrypted'])->name('notes.encrypted');
    Route::resource('notes', NoteController::class)->except(['create']);
    Route::patch('/notes/{note}/archive', [NoteController::class, 'archive'])->name('notes.archive');
    Route::patch('/notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');
    Route::patch('/notes/{note}/favorite', [NoteController::class, 'toggleFavorite'])->name('notes.favorite');

    // Tags
    Route::get('/tags', function () {
        return Inertia::render('Tags/Index');
    })->name('tags.index');

    // Groups
    Route::get('/groups', function () {
        return Inertia::render('Groups/Index');
    })->name('groups.index');

    // Sharing
    Route::get('/shared/with-me', [SharedController::class, 'withMe'])->name('shared.with-me');
    Route::get('/shared/by-me', [SharedController::class, 'byMe'])->name('shared.by-me');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
