<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CostComponent;

class CostComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // Honorarium
            ['category' => 'honorarium', 'name' => 'Fee Instruktur', 'nature' => 'R', 'role' => 'Instruktur', 'time_unit' => 'Hari', 'quantity_unit' => null, 'order' => 1],
            ['category' => 'honorarium', 'name' => 'Biaya Proktor', 'nature' => 'R', 'role' => 'Instruktur', 'time_unit' => null, 'quantity_unit' => 'Pax', 'order' => 2],
            
            // Konsumsi
            ['category' => 'konsumsi', 'name' => 'Konsumsi Kelas', 'nature' => 'R', 'role' => 'Global', 'time_unit' => 'Hari', 'quantity_unit' => 'Pax', 'order' => 3],
            ['category' => 'konsumsi', 'name' => 'Fullboard Peserta', 'nature' => 'L', 'role' => 'Peserta', 'time_unit' => 'Malam', 'quantity_unit' => 'Pax', 'order' => 4],
            ['category' => 'konsumsi', 'name' => 'Fullboard Tim Inixindo dan Instruktur', 'nature' => 'R', 'role' => 'Tim dan Instruktur', 'time_unit' => 'Malam', 'quantity_unit' => 'Pax', 'order' => 5],
            
            // ... tambahkan semua komponen lainnya dari spreadsheet
        ];

        foreach ($components as $component) {
            $category = Category::where('code', $component['category'])->first();
            
            CostComponent::create([
                'category_id' => $category->id,
                'name' => $component['name'],
                'nature' => $component['nature'],
                'role' => $component['role'],
                'time_unit' => $component['time_unit'],
                'quantity_unit' => $component['quantity_unit'],
                'order' => $component['order'],
            ]);
        }
    }
}
