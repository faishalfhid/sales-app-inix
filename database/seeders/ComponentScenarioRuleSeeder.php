<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CostComponent;
use App\Models\Scenario;
use App\Models\ComponentScenarioRule;

class ComponentScenarioRuleSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = Scenario::all();
        $components = CostComponent::all();

        // Mapping sesuai spreadsheet (Y = required, n = not required)
        $rules = [
            'Fee Instruktur' => [
                'offline_kantor' => 'y',
                'offline_inhouse_dibayar_client' => 'y',
                'offline_inhouse_dibayar_inix' => 'y',
                'online_kantor' => 'y',
                'online_inhouse' => 'y',
            ],
            // ... mapping untuk semua komponen lainnya
        ];

        foreach ($components as $component) {
            if (isset($rules[$component->name])) {
                foreach ($scenarios as $scenario) {
                    $isRequired = ($rules[$component->name][$scenario->code] ?? 'n') === 'y';
                    
                    ComponentScenarioRule::create([
                        'cost_component_id' => $component->id,
                        'scenario_id' => $scenario->id,
                        'is_required' => $isRequired,
                    ]);
                }
            }
        }
    }
}
