<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'nature',
        'role',
        'time_unit',
        'quantity_unit',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relasi ke category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke scenarios melalui pivot table
     */
    // public function scenarios()
    // {
    //     return $this->belongsToMany(Scenario::class, 'component_scenario_rules')
    //         ->withPivot('is_required', 'notes')
    //         ->withTimestamps();
    // }

    public function scenarios()
{
    return $this->belongsToMany(
        \App\Models\Scenario::class,
        'component_scenario_rules',
        'cost_component_id',
        'scenario_id'
    )
    ->withPivot(['is_required', 'notes'])
    ->withTimestamps();
}


    /**
     * Relasi ke component scenario rules
     */
    public function scenarioRules()
    {
        return $this->hasMany(ComponentScenarioRule::class);
    }

    /**
     * Relasi ke class cost details
     */
    public function classCostDetails()
    {
        return $this->hasMany(ClassCostDetail::class);
    }

    /**
     * Check apakah component required untuk scenario tertentu
     */
    public function isRequiredForScenario($scenarioId): bool
    {
        $rule = $this->scenarioRules()
            ->where('scenario_id', $scenarioId)
            ->first();
        
        return $rule ? $rule->is_required : false;
    }

    /**
     * Scope untuk real cost (R)
     */
    public function scopeRealCost($query)
    {
        return $query->where('nature', 'R');
    }

    /**
     * Scope untuk pass cost (L)
     */
    public function scopePassCost($query)
    {
        return $query->where('nature', 'L');
    }

    /**
     * Scope untuk komponen aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
