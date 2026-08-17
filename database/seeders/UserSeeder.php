<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed admin, supervisor per cabang, dan agen.
     *
     * @return void
     */
    public function run()
    {
        // Admin Super
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Syahrul (Admin)',
                'email' => 'admin@berbagi.or.id',
                'password' => Hash::make('admin12345'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $branches = Branch::all();

        // Supervisor per cabang
        foreach ($branches as $branch) {
            $supervisor = User::updateOrCreate(
                ['username' => 'supervisor_' . strtolower(str_replace(' ', '_', $branch->city))],
                [
                    'name' => 'Supervisor ' . $branch->city,
                    'email' => 'supervisor_' . strtolower(str_replace(' ', '_', $branch->city)) . '@berbagi.or.id',
                    'password' => Hash::make('super12345'),
                    'role' => User::ROLE_SUPERVISOR,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );

            $branch->supervisor_id = $supervisor->id;
            $branch->save();
        }

        // Agen contoh untuk beberapa cabang
        $agenNames = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko'];
        foreach ($branches->take(5) as $index => $branch) {
            User::updateOrCreate(
                ['username' => 'agen_' . $branch->code],
                [
                    'name' => 'Agen ' . $agenNames[$index],
                    'email' => 'agen_' . $branch->code . '@berbagi.or.id',
                    'password' => Hash::make('agen12345'),
                    'role' => User::ROLE_AGEN,
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
