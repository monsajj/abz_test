<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Lawyer'],
            ['name' => 'Content manager'],
            ['name' => 'Security'],
            ['name' => 'Designer'],
        ];

        // Insert the sample data into the positions table
        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
