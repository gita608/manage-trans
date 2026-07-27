<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TripExpenseType;

class TripExpenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenseTypes = [
            [
                'title' => 'Waiting Charge',
                'input_types' => ['hours', 'amount', 'text', 'image'],
            ],
            [
                'title' => 'Port pass',
                'input_types' => ['amount', 'text', 'image'],
            ],
            [
                'title' => 'DARB and SALIK',
                'input_types' => ['amount', 'text', 'image'],
            ],
            [
                'title' => 'Parking',
                'input_types' => ['amount', 'text', 'image'],
            ],
            [
                'title' => 'Fuel',
                'input_types' => ['amount', 'text', 'image'],
            ],
            [
                'title' => 'Maintenance',
                'input_types' => ['amount', 'text', 'image'],
            ],
            [
                'title' => 'Other / Miscellaneous',
                'input_types' => ['amount', 'hours', 'text', 'image'],
            ],
        ];

        foreach ($expenseTypes as $type) {
            TripExpenseType::updateOrCreate(
                ['title' => $type['title']],
                ['input_types' => $type['input_types']]
            );
        }
    }
}
