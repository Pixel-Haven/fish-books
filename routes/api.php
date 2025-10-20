<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CrewMemberController;
use App\Http\Controllers\Api\VesselController;
use App\Http\Controllers\Api\FishTypeController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\WeeklySheetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected routes - using 'auth' middleware for session-based auth
Route::middleware('auth')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Crew Members (OWNER & MANAGER can view/create, OWNER can delete)
    Route::apiResource('crew-members', CrewMemberController::class);
    Route::post('crew-members/{id}/restore', [CrewMemberController::class, 'restore'])
        ->middleware('role:OWNER');

    // Vessels (OWNER only)
    Route::apiResource('vessels', VesselController::class)
        ->middleware('role:OWNER');
    Route::get('vessels/{vessel}/next-week-start', [VesselController::class, 'getNextWeekStart'])
        ->middleware('role:OWNER');

    // Fish Types (OWNER only)
    Route::apiResource('fish-types', FishTypeController::class)
        ->middleware('role:OWNER');

    // Trips (OWNER & MANAGER can manage)
    Route::apiResource('trips', TripController::class);
    
    // Trip wizard steps
    Route::post('trips/{trip}/assign-crew', [TripController::class, 'assignCrew']);
    Route::post('trips/{trip}/add-bills', [TripController::class, 'addBills']);
    Route::post('trips/{trip}/add-purchases', [TripController::class, 'addPurchases']);
    Route::post('trips/{trip}/add-expenses', [TripController::class, 'addExpenses']);
    Route::post('trips/{trip}/finalize', [TripController::class, 'finalize'])
        ->middleware('role:OWNER');
    
    // Trip status management
    Route::post('trips/{trip}/close', [TripController::class, 'close'])
        ->middleware('role:OWNER');
    Route::post('trips/{trip}/reopen', [TripController::class, 'reopen'])
        ->middleware('role:OWNER');

    // Weekly Sheets (OWNER only)
    Route::apiResource('weekly-sheets', WeeklySheetController::class)
        ->middleware('role:OWNER');
    
    // Weekly sheet management
    Route::post('weekly-sheets/{weeklySheet}/add-expenses', [WeeklySheetController::class, 'addExpenses'])
        ->middleware('role:OWNER');
    Route::post('weekly-sheets/{weeklySheet}/add-credits', [WeeklySheetController::class, 'addCredits'])
        ->middleware('role:OWNER');
    Route::get('weekly-sheets/{weeklySheet}/calculate', [WeeklySheetController::class, 'calculate'])
        ->middleware('role:OWNER');
    Route::post('weekly-sheets/{weeklySheet}/finalize', [WeeklySheetController::class, 'finalize'])
        ->middleware('role:OWNER');
    Route::post('weekly-sheets/{weeklySheet}/reopen', [WeeklySheetController::class, 'reopen'])
        ->middleware('role:OWNER');
});