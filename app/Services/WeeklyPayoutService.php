<?php

namespace App\Services;

use App\Models\WeeklySheet;
use App\Models\Trip;

class WeeklyPayoutService
{
    protected TripCalculationService $tripCalculationService;

    public function __construct(TripCalculationService $tripCalculationService)
    {
        $this->tripCalculationService = $tripCalculationService;
    }

    /**
     * Calculate payouts for a weekly sheet
     *
     * @param WeeklySheet $weeklySheet
     * @return array Aggregated weekly calculations
     */
    public function calculate(WeeklySheet $weeklySheet): array
    {
        // Load relationships
        $weeklySheet->load(['weeklyExpenses', 'crewCredits.crewMember']);

        // Get all closed trips in the week range
        $trips = Trip::where('status', 'CLOSED')
            ->whereBetween('date', [$weeklySheet->week_start, $weeklySheet->week_end])
            ->with(['tripAssignments.crewMember', 'bills', 'fishPurchases', 'expenses'])
            ->get();

        // Step 1: Calculate approved weekly expenses
        $weeklyExpenseAmount = $weeklySheet->weeklyExpenses()
            ->where('status', 'APPROVED')
            ->sum('amount');

        // Step 2: Count fishing days (trips)
        $fishingDayCount = $trips->count();
        $fishingDayCount = $fishingDayCount > 0 ? $fishingDayCount : 1; // Avoid division by zero

        // Step 3: Calculate weekly expense share per trip
        $weeklyExpenseShare = $weeklyExpenseAmount / $fishingDayCount;

        // Step 4-6: Calculate each trip with weekly expense share and aggregate
        $aggregatedRevenue = [
            'today_sales' => 0,
            'previous_day_sales' => 0,
            'bill_total' => 0,
            'purchase_total' => 0,
            'crew_kilos_value' => 0,
            'total_sales' => 0,
        ];

        $aggregatedExpenses = [
            'approved' => 0,
            'weekly_share' => 0,
            'total' => 0,
        ];

        $aggregatedDistribution = [
            'balance' => 0,
            'vessel_maintenance' => 0,
            'net_total' => 0,
            'owner_share' => 0,
            'crew_share' => 0,
        ];

        $memberAggregates = [];

        foreach ($trips as $trip) {
            $tripCalc = $this->tripCalculationService->calculate($trip, $weeklyExpenseShare);

            // Aggregate revenue
            foreach ($aggregatedRevenue as $key => $value) {
                $aggregatedRevenue[$key] += $tripCalc['revenue'][$key];
            }

            // Aggregate expenses
            $aggregatedExpenses['approved'] += $tripCalc['expenses']['approved'];
            $aggregatedExpenses['weekly_share'] += $tripCalc['expenses']['weekly_share'];
            $aggregatedExpenses['total'] += $tripCalc['expenses']['total'];

            // Aggregate distribution
            foreach ($aggregatedDistribution as $key => $value) {
                $aggregatedDistribution[$key] += $tripCalc['distribution'][$key];
            }

            // Aggregate crew payouts by member
            foreach ($tripCalc['crew']['member_payouts'] as $memberPayout) {
                $memberId = $memberPayout['crew_member_id'];
                
                if (!isset($memberAggregates[$memberId])) {
                    $memberAggregates[$memberId] = [
                        'crew_member_id' => $memberId,
                        'crew_member_name' => $memberPayout['crew_member_name'],
                        'total_amount' => 0,
                        'trips_count' => 0,
                    ];
                }
                
                $memberAggregates[$memberId]['total_amount'] += $memberPayout['total_amount'];
                $memberAggregates[$memberId]['trips_count']++;
            }
        }

        // Step 7: Calculate final payouts with credit deductions
        $weeklyPayouts = [];
        foreach ($memberAggregates as $memberId => $aggregate) {
            // Get credits for this member in this week
            $creditDeduction = $weeklySheet->crewCredits()
                ->where('crew_member_id', $memberId)
                ->sum('amount');

            $finalAmount = max(0, $aggregate['total_amount'] - $creditDeduction);

            $weeklyPayouts[] = [
                'crew_member_id' => $memberId,
                'crew_member_name' => $aggregate['crew_member_name'],
                'base_amount' => round($aggregate['total_amount'], 2),
                'credit_deduction' => round($creditDeduction, 2),
                'final_amount' => round($finalAmount, 2),
                'trips_count' => $aggregate['trips_count'],
            ];
        }

        // Sort by crew member name
        usort($weeklyPayouts, fn($a, $b) => strcmp($a['crew_member_name'], $b['crew_member_name']));

        return [
            'summary' => [
                'fishing_days' => $trips->count(),
                'weekly_expense_total' => round($weeklyExpenseAmount, 2),
                'weekly_expense_per_day' => round($weeklyExpenseShare, 2),
            ],
            'revenue' => array_map(fn($v) => round($v, 2), $aggregatedRevenue),
            'expenses' => array_map(fn($v) => round($v, 2), $aggregatedExpenses),
            'distribution' => array_map(fn($v) => round($v, 2), $aggregatedDistribution),
            'payouts' => $weeklyPayouts,
        ];
    }

    /**
     * Update weekly sheet with calculated values
     */
    public function updateWeeklySheet(WeeklySheet $weeklySheet, array $calculations): void
    {
        $weeklySheet->update([
            'total_sales' => $calculations['revenue']['total_sales'],
            'total_expenses' => $calculations['expenses']['total'],
            'owner_share' => $calculations['distribution']['owner_share'],
            'crew_share' => $calculations['distribution']['crew_share'],
            'total_weekly_payout' => $calculations['distribution']['crew_share'],
        ]);
    }

    /**
     * Create or update weekly payout records
     */
    public function createPayoutRecords(WeeklySheet $weeklySheet, array $calculations): void
    {
        foreach ($calculations['payouts'] as $payoutData) {
            \App\Models\WeeklyPayout::updateOrCreate(
                [
                    'weekly_sheet_id' => $weeklySheet->id,
                    'crew_member_id' => $payoutData['crew_member_id'],
                ],
                [
                    'base_amount' => $payoutData['base_amount'],
                    'credit_deduction' => $payoutData['credit_deduction'],
                    'final_amount' => $payoutData['final_amount'],
                    'created_by' => $weeklySheet->created_by,
                ]
            );
        }
    }
}
