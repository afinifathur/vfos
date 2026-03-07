<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $expenses = [
            'makan' => ['makanan', 'cemilan', 'cafe'],
            'bill' => ['listrik', 'gas', 'internet', 'air'],
            'transportasi' => ['perawatan', 'bensin', 'parkir', 'umum'],
            'hiburan' => ['game', 'film', 'konser'],
            'kesehatan' => ['dokter', 'personal care', 'obat', 'olahraga'],
            'belanja' => ['aksesoris', 'baju', 'elektronik'],
            'travel' => ['tiket', 'kebutuhan'],
            'investasi' => ['emas', 'reksadana'],
            'education' => ['buku', 'fotokopi'],
            'donasi' => ['amal', 'pernikahan', 'pemakaman'],
        ];

        $incomes = [
            'gaji',
            'pemberian orang tua',
            'bisnis',
            'hutang',
            'menjual barang',
            'hadiah',
        ];

        foreach ($expenses as $categoryName => $subcategories) {
            $category = Category::updateOrCreate(
                ['name' => $categoryName, 'type' => 'expense'],
                ['is_active' => true]
            );

            foreach ($subcategories as $subName) {
                Subcategory::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $subName],
                    ['is_active' => true]
                );
            }
        }

        foreach ($incomes as $incomeName) {
            Category::updateOrCreate(
                ['name' => $incomeName, 'type' => 'income'],
                ['is_active' => true]
            );
        }
    }
}
