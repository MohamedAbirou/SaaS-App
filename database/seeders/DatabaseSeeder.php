<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            "name" => "Liam Pyro",
            'email' => "liam.abirou@gmail.com",
            'password' => bcrypt("qwer1234$")
        ]);

        Feature::create([
            'route_name' => 'feature1.index',
            'image' => 'https://static-00.iconduck.com/assets.00/plus-icon-2048x2048-z6v59bd6.png',
            'name' => 'Calculate Sum',
            'description' => 'Calculate the sum of two numbers',
            'required_credits' => 1,
            'active' => true
        ]);

        Feature::create([
            'route_name' => 'feature2.index',
            'image' => 'https://cdn-icons-png.freepik.com/512/929/929430.png',
            'name' => 'Calculate Difference',
            'description' => 'Calculate the difference of two numbers',
            'required_credits' => 3,
            'active' => true
        ]);

        Package::create([
            'name' => 'Basic',
            'price' => 5,
            'credits' => 20
        ]);

        Package::create([
            'name' => 'Silver',
            'price' => 20,
            'credits' => 100
        ]);

        Package::create([
            'name' => 'Gold',
            'price' => 50,
            'credits' => 500
        ]);
    }
}
