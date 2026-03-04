<?php

namespace Database\Seeders;

use App\Models\ServicePersonal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServicePersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServicePersonal::factory()->count(20)->create();
    }
}
