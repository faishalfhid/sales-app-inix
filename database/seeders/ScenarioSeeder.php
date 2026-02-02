<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scenario;

class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = [
            [
                'code' => 'offline_kantor',
                'name' => 'Offline - Kantor Inixindo',
                'type' => 'offline',
                'location' => 'kantor',
                'payment_by' => 'mixed',
                'description' => 'Pelatihan offline di kantor Inixindo',
            ],
            [
                'code' => 'offline_inhouse_dibayar_client',
                'name' => 'Offline - Inhouse (Dibayar Client)',
                'type' => 'offline',
                'location' => 'inhouse',
                'payment_by' => 'client',
                'description' => 'Pelatihan offline di lokasi client, biaya ditanggung client',
            ],
            [
                'code' => 'offline_inhouse_dibayar_inix',
                'name' => 'Offline - Inhouse (Dibayar Inixindo)',
                'type' => 'offline',
                'location' => 'inhouse',
                'payment_by' => 'inix',
                'description' => 'Pelatihan offline di lokasi client, biaya ditanggung Inixindo',
            ],
            [
                'code' => 'online_kantor',
                'name' => 'Online - Kantor Inixindo',
                'type' => 'online',
                'location' => 'kantor',
                'payment_by' => 'mixed',
                'description' => 'Pelatihan online dari kantor Inixindo',
            ],
            [
                'code' => 'online_inhouse',
                'name' => 'Online - Inhouse',
                'type' => 'online',
                'location' => 'inhouse',
                'payment_by' => 'mixed',
                'description' => 'Pelatihan online untuk inhouse client',
            ],
        ];

        foreach ($scenarios as $scenario) {
            Scenario::create($scenario);
        }
    }
}
