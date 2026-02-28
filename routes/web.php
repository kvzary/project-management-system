<?php

use App\Http\Controllers\Internal\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Internal endpoints — auth-protected, not part of any public API
Route::middleware(['auth', 'throttle:30,1'])->prefix('internal')->group(function () {
    Route::get('/users/search', [UserSearchController::class, 'search']);
});
