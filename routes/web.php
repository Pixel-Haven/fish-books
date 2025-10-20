<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

// Public routes
Route::get('/', function () {
    return redirect('/dashboard');
})->middleware('auth');

// Guest routes (not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');
    
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout (authenticated users only)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Protected routes (authenticated)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Crew Members
    Route::get('/crew-members', function () {
        return Inertia::render('CrewMembers/Index');
    })->name('crew-members.index');

    Route::get('/crew-members/create', function () {
        return Inertia::render('CrewMembers/Form');
    })->name('crew-members.create');

    Route::get('/crew-members/{id}/edit', function ($id) {
        return Inertia::render('CrewMembers/Form', ['id' => $id]);
    })->name('crew-members.edit');

    // Vessels (Owner only)
    Route::middleware('role:OWNER')->group(function () {
        Route::get('/vessels', function () {
            return Inertia::render('Vessels/Index');
        })->name('vessels.index');

        Route::get('/vessels/create', function () {
            return Inertia::render('Vessels/Form');
        })->name('vessels.create');

        Route::get('/vessels/{id}/edit', function ($id) {
            return Inertia::render('Vessels/Form', ['id' => $id]);
        })->name('vessels.edit');

        // Fish Types
        Route::get('/fish-types', function () {
            return Inertia::render('FishTypes/Index');
        })->name('fish-types.index');

        Route::get('/fish-types/create', function () {
            return Inertia::render('FishTypes/Form');
        })->name('fish-types.create');

        Route::get('/fish-types/{id}/edit', function ($id) {
            return Inertia::render('FishTypes/Form', ['id' => $id]);
        })->name('fish-types.edit');

        // Weekly Sheets
        Route::get('/weekly-sheets', function () {
            return Inertia::render('WeeklySheets/Index');
        })->name('weekly-sheets.index');

        Route::get('/weekly-sheets/create', function () {
            return Inertia::render('WeeklySheets/Create');
        })->name('weekly-sheets.create');

        Route::get('/weekly-sheets/{id}', function ($id) {
            return Inertia::render('WeeklySheets/Show', ['id' => $id]);
        })->name('weekly-sheets.show');
        
        Route::get('/weekly-sheets/{id}/edit', function ($id) {
            return Inertia::render('WeeklySheets/Create', ['id' => $id]);
        })->name('weekly-sheets.edit');
    });

    // Trips
    Route::get('/trips', function () {
        return Inertia::render('Trips/Index');
    })->name('trips.index');

    Route::get('/trips/create', function () {
        return Inertia::render('Trips/Create');
    })->name('trips.create');

    Route::get('/trips/{id}', function ($id) {
        return Inertia::render('Trips/Show', ['id' => $id]);
    })->name('trips.show');
});