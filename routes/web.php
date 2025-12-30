<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteShareController;
use App\Http\Controllers\UserPublicKeyController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [NoteController::class, 'index'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/users/public-key', [UserPublicKeyController::class, 'show'])->name('users.public-key');
    Route::get('/notes/{note}/shares', [NoteShareController::class, 'index'])->name('notes.shares.index');
    Route::post('/notes/{note}/shares', [NoteShareController::class, 'store'])->name('notes.shares.store');
    Route::delete('/notes/{note}/shares/{noteKey}', [NoteShareController::class, 'destroy'])->name('notes.shares.destroy');

    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
});
