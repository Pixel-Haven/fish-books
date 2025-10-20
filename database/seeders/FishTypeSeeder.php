<?php

namespace Database\Seeders;

use App\Models\FishType;
use App\Models\FishTypeRate;
use App\Models\User;
use Illuminate\Database\Seeder;

class FishTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('role', 'OWNER')->first();
        
        if (!$owner) {
            $this->command->error('Owner user not found. Run UserSeeder first.');
            return;
        }

        $fishTypes = [
            ['name' => 'Damaged Fish', 'rate' => 8.00],
            ['name' => 'Proper Fish', 'rate' => 16.00],
            ['name' => 'Quality Fish', 'rate' => 17.00],
            ['name' => 'Other', 'rate' => 10.00],
        ];

        foreach ($fishTypes as $fishTypeData) {
            // Create or update fish type
            $fishType = FishType::updateOrCreate(
                ['name' => $fishTypeData['name']],
                [
                    'name' => $fishTypeData['name'],
                    'default_rate_per_kilo' => $fishTypeData['rate'],
                    'created_by' => $owner->id,
                ]
            );

            // Create or update current rate record
            FishTypeRate::updateOrCreate(
                [
                    'fish_type_id' => $fishType->id,
                    'is_active' => true,
                ],
                [
                    'fish_type_id' => $fishType->id,
                    'rate_per_kilo' => $fishTypeData['rate'],
                    'rate_effective_from' => now()->toDateString(),
                    'rate_effective_to' => '2099-12-31',
                    'is_active' => true,
                ]
            );

            $this->command->info("Fish type '{$fishTypeData['name']}' seeded with rate {$fishTypeData['rate']} MVR/kg");
        }

        $this->command->info('Fish types and rates seeded successfully!');
    }
}
