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
            ['name' => 'HR'],
            ['name' => 'Manager'],
            ['name' => 'Developer'],
            ['name' => 'Designer'],
            ['name' => 'QA Tester'],
            ['name' => 'Product Owner'],
        ];

        // Insert the sample data into the positions table
        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
