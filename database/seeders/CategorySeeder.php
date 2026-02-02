<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'honorarium', 'name' => 'Honorarium', 'order' => 1],
            ['code' => 'konsumsi', 'name' => 'Konsumsi', 'order' => 2],
            ['code' => 'transportasi', 'name' => 'Transportasi', 'order' => 3],
            ['code' => 'akomodasi', 'name' => 'Akomodasi', 'order' => 4],
            ['code' => 'insentif', 'name' => 'Uang Saku / Insentif', 'order' => 5],
            ['code' => 'matper', 'name' => 'Materi & Perlengkapan', 'order' => 6],
            ['code' => 'fasilitas', 'name' => 'Fasilitas & Infrastruktur', 'order' => 7],
            ['code' => 'tambahan', 'name' => 'Kegiatan Tambahan', 'order' => 8],
            ['code' => 'pajak', 'name' => 'Pajak', 'order' => 9],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
