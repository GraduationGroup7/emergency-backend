<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Authority;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Admin
        User::query()->updateOrCreate([
            'email' => 'admin@mail.com',
        ],[
            'name' => 'admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('123123'),
            'type' => 'admin'
        ]);

        // Customer
        $user = User::query()->updateOrCreate([
            'email' => 'kaan@customer.com',
        ],[
            'name' => 'Kaan Customer',
            'email' => 'kaan@customer.com',
            'password' => Hash::make('123123'),
            'type' => 'user'
        ]);
        Customer::query()->updateOrCreate([
            'user_id' => $user->id,
        ],[
            'first_name' => 'Kaan',
            'last_name' => 'Customer',
            'dob' => '2000-05-28',
            'user_id' => $user->id,
        ]);

        // Agent
        $user = User::query()->updateOrCreate([
            'email' => 'alan@agent.com',
        ],[
            'name' => 'Alan Agent',
            'email' => 'alan@agent.com',
            'password' => Hash::make('123123'),
            'type' => 'user'
        ]);
        Agent::query()->updateOrCreate([
            'user_id' => $user->id
        ],[
            'first_name' => 'Alan',
            'last_name' => 'Agent',
            'agent_type_id' => 1,
            'user_id' => $user->id
        ]);

        // Authority
        $user = User::query()->updateOrCreate([
            'email' => 'bashar@authority.com',
        ],[
            'name' => 'Bashar Authority',
            'email' => 'bashar@authority.com',
            'password' => Hash::make('123123'),
            'type' => 'user'
        ]);
        Authority::query()->updateOrCreate([
            'user_id' => $user->id
        ],[
            'first_name' => 'Bashar',
            'last_name' => 'Authority',
            'authority_type_id' => 1,
            'user_id' => $user->id
        ]);
    }
}
