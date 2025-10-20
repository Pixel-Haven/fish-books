<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\Trip;
use App\Models\TripAssignment;
use App\Models\Bill;
use App\Models\BillLineItem;
use App\Models\FishPurchase;
use App\Models\Expense;
use App\Services\TripCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    protected TripCalculationService $calculationService;

    public function __construct(TripCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Display a listing of trips with filters
     */
    public function index(Request $request)
    {
        $query = Trip::query()
            ->with(['vessel', 'weeklySheet', 'creator', 'tripAssignments.crewMember']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by vessel
        if ($request->has('vessel_id')) {
            $query->where('vessel_id', $request->input('vessel_id'));
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->dateRange($request->input('from_date'), $request->input('to_date'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Store a newly created trip (Step 1: Basics)
     */
    public function store(StoreTripRequest $request)
    {
        $trip = Trip::create([
            ...$request->validated(),
            'status' => 'DRAFT',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Trip created successfully.',
            'data' => $trip->load(['vessel', 'creator']),
        ], 201);
    }

    /**
     * Display the specified trip with full details
     */
    public function show(Trip $trip)
    {
        $trip->load([
            'vessel',
            'creator',
            'tripAssignments.crewMember',
            'bills.lineItems.fishType',
            'fishPurchases.fishType',
            'expenses.creator',
        ]);

        // Calculate trip totals
        $calculations = $this->calculationService->calculate($trip);

        return response()->json([
            'trip' => $trip,
            'calculations' => $calculations,
        ]);
    }

    /**
     * Update the specified trip
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        if ($trip->status === 'CLOSED' && $request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can modify closed trips.',
            ], 403);
        }

        $trip->update($request->validated());

        // Recalculate if trip is ongoing or closed
        if (in_array($trip->status, ['ONGOING', 'CLOSED'])) {
            $calculations = $this->calculationService->calculate($trip);
            $this->calculationService->updateTrip($trip, $calculations);
        }

        return response()->json([
            'message' => 'Trip updated successfully.',
            'data' => $trip->load(['vessel', 'creator']),
        ]);
    }

    /**
     * Remove the specified trip
     */
    public function destroy(Trip $trip, Request $request)
    {
        // Only OWNER can delete
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can delete trips.',
            ], 403);
        }

        // Cannot delete closed trips
        if ($trip->status === 'CLOSED') {
            return response()->json([
                'message' => 'Cannot delete closed trips.',
            ], 422);
        }

        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully.',
        ]);
    }

    /**
     * Step 2: Assign crew members to trip
     */
    public function assignCrew(Request $request, Trip $trip)
    {
        $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.crew_member_id' => ['required', 'exists:crew_members,id'],
            'assignments.*.role' => ['required', 'in:BAITING,FISHING,CHUMMER,DIVING,HELPER,SPECIAL'],
            'assignments.*.helper_ratio' => ['nullable', 'numeric', 'min:0.1', 'max:2.0'],
        ]);

        if (!$trip->isEditable()) {
            return response()->json(['message' => 'Trip is not editable.'], 422);
        }

        DB::transaction(function () use ($trip, $request) {
            // Remove existing assignments
            $trip->tripAssignments()->delete();

            // Create new assignments
            foreach ($request->input('assignments') as $assignment) {
                TripAssignment::create([
                    'trip_id' => $trip->id,
                    'crew_member_id' => $assignment['crew_member_id'],
                    'role' => $assignment['role'],
                    'helper_ratio' => $assignment['helper_ratio'] ?? 1.0,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json([
            'message' => 'Crew assigned successfully.',
            'data' => $trip->load('tripAssignments.crewMember'),
        ]);
    }

    /**
     * Step 3: Add bills and line items
     */
    public function addBills(Request $request, Trip $trip)
    {
        $request->validate([
            'bills' => ['required', 'array', 'min:1'],
            'bills.*.bill_type' => ['required', 'in:TODAY_SALES,PREVIOUS_DAY_SALES,OTHER'],
            'bills.*.bill_no' => ['required', 'string', 'max:100'],
            'bills.*.vendor' => ['nullable', 'string', 'max:255'],
            'bills.*.bill_date' => ['required', 'date'],
            'bills.*.amount' => ['required', 'numeric', 'min:0'],
            'bills.*.payment_status' => ['required', 'in:PAID,UNPAID,PARTIAL'],
            'bills.*.line_items' => ['nullable', 'array'],
            'bills.*.line_items.*.fish_type_id' => ['required', 'exists:fish_types,id'],
            'bills.*.line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'bills.*.line_items.*.price_per_kilo' => ['required', 'numeric', 'min:0'],
        ]);

        if (!$trip->isEditable()) {
            return response()->json(['message' => 'Trip is not editable.'], 422);
        }

        DB::transaction(function () use ($trip, $request) {
            foreach ($request->input('bills') as $billData) {
                $lineItems = $billData['line_items'] ?? [];
                unset($billData['line_items']);

                $bill = Bill::create([
                    'trip_id' => $trip->id,
                    ...$billData,
                    'created_by' => $request->user()->id,
                ]);

                // Create line items
                foreach ($lineItems as $item) {
                    BillLineItem::create([
                        'bill_id' => $bill->id,
                        'fish_type_id' => $item['fish_type_id'],
                        'quantity' => $item['quantity'],
                        'price_per_kilo' => $item['price_per_kilo'],
                        'line_total' => $item['quantity'] * $item['price_per_kilo'],
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Bills added successfully.',
            'data' => $trip->load('bills.lineItems.fishType'),
        ]);
    }

    /**
     * Step 4: Add fish purchases
     */
    public function addPurchases(Request $request, Trip $trip)
    {
        $request->validate([
            'purchases' => ['required', 'array', 'min:1'],
            'purchases.*.fish_type_id' => ['required', 'exists:fish_types,id'],
            'purchases.*.kilos' => ['required', 'numeric', 'min:0'],
            'purchases.*.rate_per_kilo' => ['required', 'numeric', 'min:0'],
            'purchases.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        if (!$trip->isEditable()) {
            return response()->json(['message' => 'Trip is not editable.'], 422);
        }

        foreach ($request->input('purchases') as $purchase) {
            FishPurchase::create([
                'trip_id' => $trip->id,
                ...$purchase,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Fish purchases added successfully.',
            'data' => $trip->load('fishPurchases.fishType'),
        ]);
    }

    /**
     * Step 5: Add expenses
     */
    public function addExpenses(Request $request, Trip $trip)
    {
        $request->validate([
            'expenses' => ['required', 'array', 'min:1'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0'],
            'expenses.*.description' => ['required', 'string', 'max:500'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
        ]);

        if (!$trip->isEditable()) {
            return response()->json(['message' => 'Trip is not editable.'], 422);
        }

        foreach ($request->input('expenses') as $expense) {
            Expense::create([
                'trip_id' => $trip->id,
                ...$expense,
                'status' => 'PENDING',
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Expenses added successfully.',
            'data' => $trip->load('expenses'),
        ]);
    }

    /**
     * Step 6: Finalize trip
     */
    public function finalize(Request $request, Trip $trip)
    {
        // Only OWNER can finalize
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can finalize trips.',
            ], 403);
        }

        // Validation: Must have crew, bills, and purchases
        if ($trip->tripAssignments()->count() === 0) {
            return response()->json([
                'message' => 'Cannot finalize trip without crew assignments.',
            ], 422);
        }

        DB::transaction(function () use ($trip) {
            // Calculate final totals
            $calculations = $this->calculationService->calculate($trip);
            $this->calculationService->updateTrip($trip, $calculations);

            // Update status
            $trip->update([
                'status' => 'ONGOING',
            ]);
        });

        return response()->json([
            'message' => 'Trip finalized successfully.',
            'data' => $trip->load([
                'vessel',
                'tripAssignments.crewMember',
                'bills',
                'fishPurchases',
                'expenses',
            ]),
        ]);
    }

    /**
     * Close a trip
     */
    public function close(Request $request, Trip $trip)
    {
        // Only OWNER can close
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can close trips.',
            ], 403);
        }

        if ($trip->status === 'CLOSED') {
            return response()->json([
                'message' => 'Trip is already closed.',
            ], 422);
        }

        DB::transaction(function () use ($trip) {
            // Recalculate before closing
            $calculations = $this->calculationService->calculate($trip);
            $this->calculationService->updateTrip($trip, $calculations);

            $trip->update([
                'status' => 'CLOSED',
                'closed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Trip closed successfully.',
            'data' => $trip,
        ]);
    }

    /**
     * Reopen a closed trip
     */
    public function reopen(Request $request, Trip $trip)
    {
        // Only OWNER can reopen
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can reopen trips.',
            ], 403);
        }

        if ($trip->status !== 'CLOSED') {
            return response()->json([
                'message' => 'Only closed trips can be reopened.',
            ], 422);
        }

        $trip->update([
            'status' => 'ONGOING',
            'closed_at' => null,
        ]);

        return response()->json([
            'message' => 'Trip reopened successfully.',
            'data' => $trip,
        ]);
    }
}
