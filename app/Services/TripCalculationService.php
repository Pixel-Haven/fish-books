<?php

namespace App\Services;

use App\Models\Trip;

class TripCalculationService
{
    /**
     * Calculate all financial metrics for a trip
     *
     * @param Trip $trip
     * @param float $weeklyExpenseShare Optional weekly expense allocation per trip
     * @return array Comprehensive calculation results
     */
    public function calculate(Trip $trip, float $weeklyExpenseShare = 0): array
    {
        // Load all necessary relationships
        $trip->load([
            'bills.lineItems.fishType',
            'fishPurchases.fishType',
            'expenses',
            'tripAssignments.crewMember'
        ]);

        // Step 1-2: Calculate sales from bills
        $todaySales = $trip->bills()
            ->where('bill_type', 'TODAY_SALES')
            ->sum('amount');
            
        $previousSales = $trip->bills()
            ->where('bill_type', 'PREVIOUS_DAY_SALES')
            ->sum('amount');
            
        $billTotal = $todaySales + $previousSales;

        // Step 3: Calculate purchase total
        $purchaseTotal = $trip->fishPurchases->sum('amount');

        // Step 4: Calculate crew baseline kilos value
        $crewKilos = $this->calculateCrewBaselineKilos($trip);
        $properFishRate = $this->getProperFishRate($trip);
        $crewKilosValue = $crewKilos * $properFishRate;

        // Step 5: Total sales
        $totalSales = $billTotal + $purchaseTotal + $crewKilosValue;

        // Step 6: Calculate expenses
        $approvedExpenses = $trip->expenses()
            ->where('status', 'APPROVED')
            ->sum('amount');
            
        $pendingExpenses = $trip->expenses()
            ->where('status', 'PENDING')
            ->sum('amount');

        // Step 7: Total expenses (includes weekly expense share if provided)
        $totalExpenses = $approvedExpenses + $weeklyExpenseShare;

        // Step 8: Balance after expenses
        $balance = $totalSales - $totalExpenses;

        // Step 9: Vessel maintenance (10% of balance)
        $vesselMaintenance = $balance * 0.10;

        // Step 10: Net total after maintenance
        $netTotal = $balance - $vesselMaintenance;

        // Step 11: Owner share (1/3 of net total)
        $ownerShare = $netTotal / 3;

        // Step 12: Crew share (2/3 of net total)
        $crewShare = $netTotal * (2 / 3);

        // Step 13-16: Calculate per-member payouts
        $crewCalculations = $this->calculateCrewPayouts($trip, $crewShare);

        return [
            'revenue' => [
                'today_sales' => round($todaySales, 2),
                'previous_day_sales' => round($previousSales, 2),
                'bill_total' => round($billTotal, 2),
                'purchase_total' => round($purchaseTotal, 2),
                'crew_kilos' => round($crewKilos, 2),
                'crew_kilos_value' => round($crewKilosValue, 2),
                'total_sales' => round($totalSales, 2),
            ],
            'expenses' => [
                'approved' => round($approvedExpenses, 2),
                'pending' => round($pendingExpenses, 2),
                'weekly_share' => round($weeklyExpenseShare, 2),
                'total' => round($totalExpenses, 2),
            ],
            'distribution' => [
                'balance' => round($balance, 2),
                'vessel_maintenance' => round($vesselMaintenance, 2),
                'net_total' => round($netTotal, 2),
                'owner_share' => round($ownerShare, 2),
                'crew_share' => round($crewShare, 2),
            ],
            'crew' => $crewCalculations,
        ];
    }

    /**
     * Calculate baseline kilos for crew (baiting and fishing roles get 4kg each)
     */
    protected function calculateCrewBaselineKilos(Trip $trip): float
    {
        $kilos = 0;
        
        foreach ($trip->tripAssignments as $assignment) {
            if (in_array($assignment->role, ['BAITING', 'FISHING'])) {
                $kilos += 4.0;
            }
        }
        
        return $kilos;
    }

    /**
     * Get the proper fish rate for the trip date (fallback to 16.00)
     */
    protected function getProperFishRate(Trip $trip): float
    {
        $properFish = \App\Models\FishType::where('name', 'Proper Fish')->first();
        
        if ($properFish) {
            return $properFish->getCurrentRate($trip->date->format('Y-m-d'));
        }
        
        return 16.00;
    }

    /**
     * Calculate individual crew member payouts based on role weights
     */
    protected function calculateCrewPayouts(Trip $trip, float $crewShare): array
    {
        $assignments = $trip->tripAssignments;
        
        // Calculate total weight units
        $totalWeightUnits = 0;
        $memberWeights = [];
        
        foreach ($assignments as $assignment) {
            $weight = $this->getRoleWeight($assignment);
            $memberId = $assignment->crew_member_id;
            
            if (!isset($memberWeights[$memberId])) {
                $memberWeights[$memberId] = [
                    'crew_member' => $assignment->crewMember,
                    'total_weight' => 0,
                    'roles' => [],
                ];
            }
            
            $memberWeights[$memberId]['total_weight'] += $weight;
            $memberWeights[$memberId]['roles'][] = [
                'role' => $assignment->role,
                'weight' => $weight,
            ];
            
            $totalWeightUnits += $weight;
        }
        
        // Calculate per-unit value (guard against division by zero)
        $perUnitValue = $totalWeightUnits > 0 ? $crewShare / $totalWeightUnits : 0;
        
        // Calculate individual payouts
        $crewPayouts = [];
        foreach ($memberWeights as $memberId => $data) {
            $totalAmount = $data['total_weight'] * $perUnitValue;
            
            $crewPayouts[] = [
                'crew_member_id' => $memberId,
                'crew_member_name' => $data['crew_member']->name,
                'total_weight' => round($data['total_weight'], 2),
                'roles' => $data['roles'],
                'total_amount' => round($totalAmount, 2),
            ];
        }
        
        return [
            'total_weight_units' => round($totalWeightUnits, 2),
            'per_unit_value' => round($perUnitValue, 2),
            'member_payouts' => $crewPayouts,
        ];
    }

    /**
     * Get the weight/units for a specific role
     */
    protected function getRoleWeight($assignment): float
    {
        return match ($assignment->role) {
            'DIVING' => 1.0,
            'BAITING', 'FISHING', 'CHUMMER', 'HELPER' => 0.5,
            'SPECIAL' => 0.5 * ($assignment->helper_ratio ?? 1.0),
            default => 0.5,
        };
    }

    /**
     * Update trip with calculated values
     */
    public function updateTrip(Trip $trip, array $calculations): void
    {
        $trip->update([
            'total_sales' => $calculations['revenue']['total_sales'],
            'balance' => $calculations['distribution']['balance'],
            'net_total' => $calculations['distribution']['net_total'],
            'owner_share' => $calculations['distribution']['owner_share'],
            'crew_share' => $calculations['distribution']['crew_share'],
        ]);
    }
}
