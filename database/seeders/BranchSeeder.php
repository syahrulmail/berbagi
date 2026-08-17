<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Seed 30 cabang BWA dengan target default 50 juta.
     *
     * @return void
     */
    public function run()
    {
        $cities = [
            'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Barat', 'Jakarta Utara',
            'Bandung', 'Bogor', 'Depok', 'Tangerang', 'Bekasi',
            'Semarang', 'Yogyakarta', 'Solo', 'Purwokerto', 'Cilacap',
            'Surabaya', 'Malang', 'Sidoarjo', 'Madiun', 'Kediri',
            'Medan', 'Padang', 'Palembang', 'Pekanbaru', 'Banda Aceh',
            'Makassar', 'Manado', 'Banjarmasin', 'Balikpapan', 'Denpasar',
        ];

        foreach ($cities as $index => $city) {
            Branch::updateOrCreate(
                ['code' => sprintf('BWA-%02d', $index + 1)],
                [
                    'name' => 'Cabang ' . $city,
                    'city' => $city,
                    'target_amount' => 50000000,
                    'is_active' => true,
                ]
            );
        }
    }
}
