<?php

namespace Database\Seeders;

use App\Models\EmergencyType;
use Illuminate\Database\Seeder;

class EmergencyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EmergencyType::query()->updateOrCreate(['name' => 'Fire'], [
            'name' => 'Fire',
        ]);
        EmergencyType::query()->updateOrCreate(['name' => 'Police'], [
            'name' => 'Police',
        ]);
        EmergencyType::query()->updateOrCreate(['name' => 'Medical'], [
            'name' => 'Medical',
        ]);
        EmergencyType::query()->updateOrCreate(['name' => 'Other'], [
            'name' => 'Other',
        ]);
    }
}
