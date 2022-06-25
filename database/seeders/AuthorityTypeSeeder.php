<?php

namespace Database\Seeders;

use App\Models\AuthorityType;
use Illuminate\Database\Seeder;

class AuthorityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AuthorityType::query()->updateOrCreate(['name' => 'Fire'], [
            'name' => 'Fire',
        ]);
        AuthorityType::query()->updateOrCreate(['name' => 'Police'], [
            'name' => 'Police',
        ]);
        AuthorityType::query()->updateOrCreate(['name' => 'Medical'], [
            'name' => 'Medical',
        ]);
        AuthorityType::query()->updateOrCreate(['name' => 'Other'], [
            'name' => 'Other',
        ]);
    }
}
