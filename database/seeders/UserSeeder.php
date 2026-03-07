<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create user 1 — afini (owns all existing data)
        $afini = User::updateOrCreate(
            ['email' => 'afini.fathurrorzi@gmail.com'],
            [
                'name'     => 'Afini Fathurrorzi',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create user 2 — alifia (empty data)
        User::updateOrCreate(
            ['email' => 'alifianinulil@gmail.com'],
            [
                'name'     => 'Alifia Ninulil',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign all existing data to afini (user 1)
        $tables = ['accounts', 'categories', 'budgets', 'debts', 'receivables', 'investments', 'assets'];

        foreach ($tables as $table) {
            DB::table($table)->whereNull('user_id')->update(['user_id' => $afini->id]);
        }

        $this->command->info("✅ Users seeded! Existing data assigned to afini.fathurrorzi@gmail.com (ID: {$afini->id})");
    }
}
