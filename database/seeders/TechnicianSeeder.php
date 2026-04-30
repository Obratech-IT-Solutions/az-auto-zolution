<?php

namespace Database\Seeders;

use App\Models\Technician;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    /**
     * Technicians have no login; name + position only.
     */
    public function run(): void
    {
        $position = 'Technician';

        foreach (
            [
                'Greg',
                'Dexter',
                'Jeric',
                'Marvin',
                'Lucas',
                'Rowel',
                'Jake',
                'Daryll',
            ] as $name
        ) {
            Technician::updateOrCreate(
                ['name' => $name],
                ['position' => $position]
            );
        }
    }
}
