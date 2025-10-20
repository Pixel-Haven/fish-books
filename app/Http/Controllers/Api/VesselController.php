<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Models\Vessel;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    public function index(Request $request)
    {
        $query = Vessel::query()->with('creator');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        if ($request->boolean('all')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreVesselRequest $request)
    {
        $vessel = Vessel::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Vessel created successfully.',
            'data' => $vessel->load('creator'),
        ], 201);
    }

    public function show(Vessel $vessel)
    {
        $vessel->load(['creator', 'trips' => function ($query) {
            $query->latest()->limit(10);
        }]);
        
        return response()->json($vessel);
    }

    public function update(UpdateVesselRequest $request, Vessel $vessel)
    {
        $vessel->update($request->validated());

        return response()->json([
            'message' => 'Vessel updated successfully.',
            'data' => $vessel->load('creator'),
        ]);
    }

    public function destroy(Vessel $vessel)
    {
        if ($vessel->trips()->exists()) {
            return response()->json([
                'message' => 'Cannot delete vessel with trip history. Consider marking as inactive instead.',
            ], 422);
        }

        $vessel->delete();

        return response()->json([
            'message' => 'Vessel deleted successfully.',
        ]);
    }

    public function getNextWeekStart(Vessel $vessel)
    {
        // Get the latest weekly sheet for this vessel
        $latestWeek = $vessel->weeklySheets()
            ->orderBy('week_start', 'desc')
            ->first();

        if (!$latestWeek) {
            // No previous weeks, suggest this Saturday if no data, or next Saturday
            $today = now();
            $dayOfWeek = $today->dayOfWeek;
            
            // If it's already Saturday and early (before noon), suggest today
            if ($dayOfWeek === 6 && $today->hour < 12) {
                return response()->json([
                    'suggested_date' => $today->toDateString(),
                ]);
            }
            
            // Otherwise, next Saturday
            $daysUntilSaturday = (6 - $dayOfWeek + 7) % 7 ?: 7;
            $nextSaturday = $today->copy()->addDays($daysUntilSaturday);
            
            return response()->json([
                'suggested_date' => $nextSaturday->toDateString(),
            ]);
        }

        // Has previous weeks, suggest next Saturday after the last week
        $lastWeekEnd = \Carbon\Carbon::parse($latestWeek->week_end);
        $nextSaturday = $lastWeekEnd->copy()->addDay(); // Friday + 1 = Saturday
        
        return response()->json([
            'suggested_date' => $nextSaturday->toDateString(),
        ]);
    }
}
