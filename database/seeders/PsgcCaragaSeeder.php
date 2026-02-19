<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PsgcCaragaSeeder extends Seeder
{
    public function run(): void
    {
        if (Province::count() > 0) {
            return;
        }

        $data = require database_path('seeders/data/caraga-psgc.php');

        DB::transaction(function () use ($data) {
            foreach ($data as $provinceName => $municipalities) {
                $province = Province::create(['name' => $provinceName]);

                foreach ($municipalities as $municipalityName => $barangays) {
                    $municipality = $province->municipalities()->create(['name' => $municipalityName]);

                    $barangayRecords = array_map(
                        fn (string $name) => ['name' => $name, 'municipality_id' => $municipality->id],
                        $barangays,
                    );

                    $municipality->barangays()->insert($barangayRecords);
                }
            }
        });
    }
}
