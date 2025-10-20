<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFishTypeRequest;
use App\Http\Requests\UpdateFishTypeRequest;
use App\Models\FishType;
use App\Models\FishTypeRate;
use Illuminate\Http\Request;

class FishTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = FishType::query()->with(['creator', 'rates' => function ($query) {
            $query->active()->latest();
        }])->withCount('rates');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $query->orderBy('name');

        if ($request->boolean('all')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreFishTypeRequest $request)
    {
        $fishType = FishType::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        // Create initial rate record
        FishTypeRate::create([
            'fish_type_id' => $fishType->id,
            'rate_per_kilo' => $request->input('default_rate_per_kilo'),
            'rate_effective_from' => now()->toDateString(),
            'rate_effective_to' => '2099-12-31',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Fish type created successfully.',
            'data' => $fishType->load(['creator', 'rates']),
        ], 201);
    }

    public function show(FishType $fishType)
    {
        $fishType->load(['creator', 'rates' => function ($query) {
            $query->orderBy('rate_effective_from', 'desc');
        }]);
        
        // Format rates with simplified structure for frontend
        $formattedRates = $fishType->rates->map(function ($rate) {
            return [
                'id' => $rate->id,
                'rate' => (float) $rate->rate_per_kilo,
                'effective_from' => $rate->rate_effective_from->toDateString(),
                'created_at' => $rate->created_at->toDateString(),
            ];
        })->values()->all();
        
        // Convert to array and replace rates
        $response = $fishType->toArray();
        $response['rates'] = $formattedRates;
        
        return response()->json($response);
    }

    public function update(UpdateFishTypeRequest $request, FishType $fishType)
    {
        $fishType->update($request->validated());

        // If default rate changed, create new rate record
        if ($request->has('default_rate_per_kilo')) {
            FishTypeRate::create([
                'fish_type_id' => $fishType->id,
                'rate_per_kilo' => $request->input('default_rate_per_kilo'),
                'rate_effective_from' => now()->toDateString(),
                'rate_effective_to' => '2099-12-31',
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Fish type updated successfully.',
            'data' => $fishType->load(['creator', 'rates']),
        ]);
    }

    public function destroy(FishType $fishType)
    {
        if ($fishType->fishPurchases()->exists() || $fishType->billLineItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete fish type with transaction history.',
            ], 422);
        }

        $fishType->delete();

        return response()->json([
            'message' => 'Fish type deleted successfully.',
        ]);
    }
}
