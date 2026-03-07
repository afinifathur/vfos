<?php

namespace Database\Seeders;

use App\Models\User;
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
        // 1. Seed Accounts
        \App\Models\Account::create(['name' => 'BCA (Afin)', 'type' => 'bank', 'owner' => 'afin']);
        \App\Models\Account::create(['name' => 'Cash (Afin)', 'type' => 'cash', 'owner' => 'afin']);
        \App\Models\Account::create(['name' => 'Gopay (Pacar)', 'type' => 'ewallet', 'owner' => 'pacar']);

        // 2. Seed Categories & Subcategories
        $income = \App\Models\Category::create(['name' => 'Income', 'type' => 'income']);
        $income->subcategories()->createMany([
            ['name' => 'Salary'],
            ['name' => 'Bonus'],
            ['name' => 'Gift'],
        ]);

        $spending = \App\Models\Category::create(['name' => 'Spending', 'type' => 'expense']);
        $spending->subcategories()->createMany([
            ['name' => 'Food & Drink'],
            ['name' => 'Transport'],
            ['name' => 'Rent'],
            ['name' => 'Shopping'],
        ]);

        $bills = \App\Models\Category::create(['name' => 'Bills', 'type' => 'expense']);
        $bills->subcategories()->createMany([
            ['name' => 'Electricity'],
            ['name' => 'Internet'],
            ['name' => 'Water'],
        ]);
    }
}
