<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WearCoefficientsSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Segment GRAVEL (Profil de Marc)
            ['segment' => 'GRAVEL', 'terrain' => 'ASPHALT', 'km_to_eol_baseline' => 4000, 'coef' => 0.8],
            ['segment' => 'GRAVEL', 'terrain' => 'HARDPACKED', 'km_to_eol_baseline' => 4000, 'coef' => 1.1],
            ['segment' => 'GRAVEL', 'terrain' => 'MIXED', 'km_to_eol_baseline' => 4000, 'coef' => 1.3],
            ['segment' => 'GRAVEL', 'terrain' => 'SOFT', 'km_to_eol_baseline' => 4000, 'coef' => 1.2],
            ['segment' => 'GRAVEL', 'terrain' => 'MUD', 'km_to_eol_baseline' => 4000, 'coef' => 1.4],
            
            // Segment ROAD (Profil de Sophie)
            ['segment' => 'ROAD', 'terrain' => 'ASPHALT', 'km_to_eol_baseline' => 5000, 'coef' => 1.0],
        ];

        foreach ($data as $row) {
            DB::table('wear_coefficients')->updateOrInsert(
                ['segment' => $row['segment'], 'terrain' => $row['terrain']],
                [
                    'km_to_eol_baseline' => $row['km_to_eol_baseline'],
                    'coef' => $row['coef'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
