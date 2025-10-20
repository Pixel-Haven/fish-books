<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeeklySheetRequest;
use App\Http\Requests\UpdateWeeklySheetRequest;
use App\Models\WeeklySheet;
use App\Models\WeeklyExpense;
use App\Models\CrewCredit;
use App\Models\WeeklyPayout;
use App\Services\WeeklyPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklySheetController extends Controller
{
    protected WeeklyPayoutService $payoutService;

    public function __construct(WeeklyPayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
    }

    /**
     * Display a listing of weekly sheets
     */
    public function index(Request $request)
    {
        $query = WeeklySheet::query()
            ->with(['vessel', 'creator', 'trips'])
            ->withCount([
                'trips',
                'trips as closed_trips_count' => function ($query) {
                    $query->where('status', 'CLOSED');
                },
                'trips as fishing_days_count' => function ($query) {
                    $query->where('is_fishing_day', true);
                }
            ]);

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
            $query->whereDate('week_start', '>=', $request->input('from_date'))
                  ->whereDate('week_end', '<=', $request->input('to_date'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'week_start');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $sheets = $query->get();

        return response()->json([
            'data' => $sheets
        ]);
    }

    /**
     * Store a newly created weekly sheet
     */
    public function store(StoreWeeklySheetRequest $request)
    {
        // Check if there's already an active week for this vessel
        $existingWeek = WeeklySheet::where('vessel_id', $request->vessel_id)
            ->whereIn('status', ['DRAFT', 'READY_FOR_APPROVAL'])
            ->exists();

        if ($existingWeek) {
            return response()->json([
                'message' => 'This vessel already has an active weekly sheet in DRAFT or READY_FOR_APPROVAL status.',
            ], 422);
        }

        // Validate date range doesn't overlap with existing sheets for this vessel
        $overlapping = WeeklySheet::where('vessel_id', $request->vessel_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('week_start', [$request->week_start, $request->week_end])
                      ->orWhereBetween('week_end', [$request->week_start, $request->week_end])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('week_start', '<=', $request->week_start)
                            ->where('week_end', '>=', $request->week_end);
                      });
            })->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'Date range overlaps with an existing weekly sheet for this vessel.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create weekly sheet
            $weeklySheet = WeeklySheet::create([
                'vessel_id' => $request->vessel_id,
                'week_start' => $request->week_start,
                'week_end' => $request->week_end,
                'label' => $request->label,
                'description' => $request->description,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            // Auto-generate 6 trips (Saturday through Thursday)
            $days = ['SAT', 'SUN', 'MON', 'TUE', 'WED', 'THU'];
            $startDate = \Carbon\Carbon::parse($request->week_start);

            for ($i = 0; $i < 6; $i++) {
                $tripDate = $startDate->copy()->addDays($i);
                
                $weeklySheet->trips()->create([
                    'vessel_id' => $request->vessel_id,
                    'day_of_week' => $days[$i],
                    'date' => $tripDate->toDateString(),
                    'is_fishing_day' => true, // Default to fishing day
                    'status' => 'DRAFT',
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            // Load relationships for response
            $weeklySheet->load(['vessel', 'trips']);

            return response()->json([
                'message' => 'Weekly sheet created successfully with 6 auto-generated trips.',
                'data' => $weeklySheet,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create weekly sheet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified weekly sheet
     */
    public function show(WeeklySheet $weeklySheet)
    {
        $weeklySheet->load([
            'creator',
            'weeklyExpenses.creator',
            'weeklyPayouts.crewMember',
            'crewCredits.crewMember',
        ]);

        return response()->json($weeklySheet);
    }

    /**
     * Update the specified weekly sheet
     */
    public function update(UpdateWeeklySheetRequest $request, WeeklySheet $weeklySheet)
    {
        // Only OWNER can modify finalized sheets
        if ($weeklySheet->status === 'FINALIZED' && $request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can modify finalized weekly sheets.',
            ], 403);
        }

        $weeklySheet->update($request->validated());

        return response()->json([
            'message' => 'Weekly sheet updated successfully.',
            'data' => $weeklySheet,
        ]);
    }

    /**
     * Remove the specified weekly sheet
     */
    public function destroy(WeeklySheet $weeklySheet, Request $request)
    {
        // Only OWNER can delete
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can delete weekly sheets.',
            ], 403);
        }

        // Cannot delete finalized sheets
        if ($weeklySheet->status === 'FINALIZED') {
            return response()->json([
                'message' => 'Cannot delete finalized weekly sheets.',
            ], 422);
        }

        $weeklySheet->delete();

        return response()->json([
            'message' => 'Weekly sheet deleted successfully.',
        ]);
    }

    /**
     * Add weekly expenses to the sheet
     */
    public function addExpenses(Request $request, WeeklySheet $weeklySheet)
    {
        $request->validate([
            'expenses' => ['required', 'array', 'min:1'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0'],
            'expenses.*.description' => ['required', 'string', 'max:500'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
        ]);

        if ($weeklySheet->status === 'FINALIZED') {
            return response()->json([
                'message' => 'Cannot add expenses to finalized weekly sheet.',
            ], 422);
        }

        foreach ($request->input('expenses') as $expense) {
            WeeklyExpense::create([
                'weekly_sheet_id' => $weeklySheet->id,
                ...$expense,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Expenses added successfully.',
            'data' => $weeklySheet->load('weeklyExpenses'),
        ]);
    }

    /**
     * Add crew credits to the sheet
     */
    public function addCredits(Request $request, WeeklySheet $weeklySheet)
    {
        $request->validate([
            'credits' => ['required', 'array', 'min:1'],
            'credits.*.crew_member_id' => ['required', 'exists:crew_members,id'],
            'credits.*.amount' => ['required', 'numeric', 'min:0'],
            'credits.*.description' => ['required', 'string', 'max:500'],
        ]);

        if ($weeklySheet->status === 'FINALIZED') {
            return response()->json([
                'message' => 'Cannot add credits to finalized weekly sheet.',
            ], 422);
        }

        foreach ($request->input('credits') as $credit) {
            CrewCredit::create([
                'weekly_sheet_id' => $weeklySheet->id,
                ...$credit,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Credits added successfully.',
            'data' => $weeklySheet->load('crewCredits.crewMember'),
        ]);
    }

    /**
     * Calculate payouts for the weekly sheet
     */
    public function calculate(WeeklySheet $weeklySheet)
    {
        $calculations = $this->payoutService->calculateWeeklyPayouts(
            $weeklySheet->from_date,
            $weeklySheet->to_date
        );

        return response()->json([
            'calculations' => $calculations,
            'weekly_sheet' => $weeklySheet->load(['weeklyExpenses', 'crewCredits']),
        ]);
    }

    /**
     * Finalize the weekly sheet and create payout records
     */
    public function finalize(Request $request, WeeklySheet $weeklySheet)
    {
        // Only OWNER can finalize
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can finalize weekly sheets.',
            ], 403);
        }

        if ($weeklySheet->status === 'FINALIZED') {
            return response()->json([
                'message' => 'Weekly sheet is already finalized.',
            ], 422);
        }

        DB::transaction(function () use ($weeklySheet, $request) {
            // Calculate payouts
            $calculations = $this->payoutService->calculateWeeklyPayouts(
                $weeklySheet->from_date,
                $weeklySheet->to_date
            );

            // Create payout records
            $this->payoutService->createPayoutRecords(
                $weeklySheet,
                $calculations,
                $request->user()->id
            );

            // Update totals and status
            $weeklySheet->update([
                'total_revenue' => $calculations['total_revenue'],
                'total_expenses' => $calculations['total_expenses'],
                'total_crew_share' => $calculations['total_crew_share'],
                'total_owner_share' => $calculations['total_owner_share'],
                'status' => 'FINALIZED',
                'finalized_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Weekly sheet finalized successfully.',
            'data' => $weeklySheet->load([
                'weeklyPayouts.crewMember',
                'weeklyExpenses',
                'crewCredits.crewMember',
            ]),
        ]);
    }

    /**
     * Reopen a finalized weekly sheet
     */
    public function reopen(Request $request, WeeklySheet $weeklySheet)
    {
        // Only OWNER can reopen
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can reopen weekly sheets.',
            ], 403);
        }

        if ($weeklySheet->status !== 'FINALIZED') {
            return response()->json([
                'message' => 'Only finalized weekly sheets can be reopened.',
            ], 422);
        }

        DB::transaction(function () use ($weeklySheet) {
            // Delete existing payout records
            $weeklySheet->weeklyPayouts()->delete();

            // Reset status
            $weeklySheet->update([
                'status' => 'DRAFT',
                'finalized_at' => null,
                'total_revenue' => 0,
                'total_expenses' => 0,
                'total_crew_share' => 0,
                'total_owner_share' => 0,
            ]);
        });

        return response()->json([
            'message' => 'Weekly sheet reopened successfully.',
            'data' => $weeklySheet,
        ]);
    }
}
