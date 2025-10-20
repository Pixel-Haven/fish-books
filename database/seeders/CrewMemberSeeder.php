<?php

namespace Database\Seeders;

use App\Models\CrewMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrewMemberSeeder extends Seeder
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

        $crewMembers = [
            [
                'name' => 'Ahmed Ali',
                'phone' => '+960 777-1234',
                'id_card_no' => 'A123456',
                'bank_name' => 'Bank of Maldives',
                'bank_account_number' => '1001234567',
                'bank_account_holder' => 'Ahmed Ali',
            ],
            [
                'name' => 'Ibrahim Hassan',
                'phone' => '+960 777-2345',
                'id_card_no' => 'A234567',
                'bank_name' => 'Maldivian Heritage Bank',
                'bank_account_number' => '2001234567',
                'bank_account_holder' => 'Ibrahim Hassan',
            ],
            [
                'name' => 'Mohamed Waheed',
                'phone' => '+960 777-3456',
                'id_card_no' => 'A345678',
                'bank_name' => 'Bank of Maldives',
                'bank_account_number' => '1002234567',
                'bank_account_holder' => 'Mohamed Waheed',
            ],
            [
                'name' => 'Ali Rasheed',
                'phone' => '+960 777-4567',
                'id_card_no' => 'A456789',
                'bank_name' => 'State Bank of India',
                'bank_account_number' => '3001234567',
                'bank_account_holder' => 'Ali Rasheed',
            ],
            [
                'name' => 'Hassan Moosa',
                'phone' => '+960 777-5678',
                'id_card_no' => 'A567890',
                'bank_name' => 'Maldivian Heritage Bank',
                'bank_account_number' => '2002234567',
                'bank_account_holder' => 'Hassan Moosa',
            ],
            [
                'name' => 'Waheed Ibrahim',
                'phone' => '+960 777-6789',
                'id_card_no' => 'A678901',
                'bank_name' => 'Bank of Maldives',
                'bank_account_number' => '1003234567',
                'bank_account_holder' => 'Waheed Ibrahim',
            ],
            [
                'name' => 'Rasheed Ahmed',
                'phone' => '+960 777-7890',
                'id_card_no' => 'A789012',
                'bank_name' => 'State Bank of India',
                'bank_account_number' => '3002234567',
                'bank_account_holder' => 'Rasheed Ahmed',
            ],
            [
                'name' => 'Moosa Ali',
                'phone' => '+960 777-8901',
                'id_card_no' => 'A890123',
                'bank_name' => 'Bank of Maldives',
                'bank_account_number' => '1004234567',
                'bank_account_holder' => 'Moosa Ali',
            ],
            [
                'name' => 'Ismail Naeem',
                'phone' => '+960 777-9012',
                'id_card_no' => 'A901234',
                'bank_name' => 'Maldivian Heritage Bank',
                'bank_account_number' => '2003234567',
                'bank_account_holder' => 'Ismail Naeem',
            ],
            [
                'name' => 'Naeem Ismail',
                'phone' => '+960 777-0123',
                'id_card_no' => 'A012345',
                'bank_name' => 'State Bank of India',
                'bank_account_number' => '3003234567',
                'bank_account_holder' => 'Naeem Ismail',
            ],
        ];

        foreach ($crewMembers as $memberData) {
            CrewMember::updateOrCreate(
                ['id_card_no' => $memberData['id_card_no']],
                array_merge($memberData, [
                    'active' => true,
                    'created_by' => $owner->id,
                ])
            );

            $this->command->info("Crew member '{$memberData['name']}' seeded successfully");
        }

        $this->command->info('All crew members seeded successfully!');
    }
}
