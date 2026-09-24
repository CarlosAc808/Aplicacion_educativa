<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Subject::firstOrCreate(['name' => 'Matemáticas'], [
            'short' => 'Mates', 'icon' => '∑', 'color' => 'orange', 'description' => 'Números, formas y retos',
        ]);
        Subject::firstOrCreate(['name' => 'Biología'], [
            'short' => 'Bio', 'icon' => '✦', 'color' => 'cyan', 'description' => 'Descubre la vida',
        ]);
        Subject::firstOrCreate(['name' => 'Español'], [
            'short' => 'Letras', 'icon' => 'Aa', 'color' => 'green', 'description' => 'Historias y palabras',
        ]);
    }
}
