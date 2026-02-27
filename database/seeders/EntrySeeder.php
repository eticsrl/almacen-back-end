<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Entry;

class EntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Entry::factory()
        ->count(50)
        ->create()
        ->each(function ($entry) {
            \App\Models\EntryDetail::factory()
                ->count(rand(1, 5)) // Al menos uno por entry
                ->create(['ingreso_id' => $entry->id]);
        });
    }
}
