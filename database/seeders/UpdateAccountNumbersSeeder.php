<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateAccountNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            1  => '0035', // BCA DEBIT
            4  => '5882', // MANDIRI PAYROLL (afin)
            14 => '1258', // MANDIRI PAYROLL (pacar)
        ];

        foreach ($mappings as $id => $number) {
            DB::table('accounts')
                ->where('id', $id)
                ->update(['account_number' => $number]);
        }
    }
}
