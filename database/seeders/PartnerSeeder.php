<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'title' => 'ZAKHER MARINE INTERNATIONAL (ZMI)',
                'is_default' => true,
            ],
            [
                'title' => 'OVERSEAS MARINE (OMS)',
                'is_default' => false,
            ],
            [
                'title' => 'TUV MIDDLE EAST (TUV)',
                'is_default' => false,
            ],
            [
                'title' => 'OCEANIC MARINE',
                'is_default' => false,
            ],
            [
                'title' => 'DGL',
                'is_default' => false,
            ],
        ];

        foreach ($partners as $partner) {
            Partner::firstOrCreate(
                ['title' => $partner['title']],
                $partner
            );
        }
    }
}
