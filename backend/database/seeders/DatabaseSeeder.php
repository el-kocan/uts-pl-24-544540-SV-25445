<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@ugm.ac.id'],
            [
                'name'     => 'Admin UGM',
                'password' => Hash::make('password123'),
            ]
        );

        // Create 3 dummy equipment data
        $equipments = [
            [
                'name'          => 'Mikroskop Olympus CX23',
                'category'      => 'Optik',
                'serial_number' => 'OLY-CX23-2024-001',
                'condition'     => 'baik',
                'quantity'      => 5,
            ],
            [
                'name'          => 'Spektrofotometer UV-Vis',
                'category'      => 'Analitik',
                'serial_number' => 'SFM-UV-2023-042',
                'condition'     => 'rusak_ringan',
                'quantity'      => 2,
            ],
            [
                'name'          => 'Sentrifuge Thermo Scientific',
                'category'      => 'Pemisahan',
                'serial_number' => 'THS-CF-2022-017',
                'condition'     => 'baik',
                'quantity'      => 3,
            ],
        ];

        foreach ($equipments as $data) {
            Equipment::firstOrCreate(
                ['serial_number' => $data['serial_number']],
                $data
            );
        }
    }
}
