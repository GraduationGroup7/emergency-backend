<?php

namespace Database\Seeders;

use App\Models\AgentType;
use Illuminate\Database\Seeder;

class AgentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AgentType::query()->updateOrCreate(['name' => 'Fire'], [
            'name' => 'Fire',
        ]);
        AgentType::query()->updateOrCreate(['name' => 'Police'], [
            'name' => 'Police',
        ]);
        AgentType::query()->updateOrCreate(['name' => 'Medical'], [
            'name' => 'Medical',
        ]);
        AgentType::query()->updateOrCreate(['name' => 'Other'], [
            'name' => 'Other',
        ]);
    }
}
