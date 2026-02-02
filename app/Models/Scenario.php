<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'location',
        'payment_by',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke cost components melalui pivot table
     */
    // public function costComponents()
    // {
    //     return $this->belongsToMany(CostComponent::class, 'component_scenario_rules')
    //         ->withPivot('is_required', 'notes')
    //         ->withTimestamps();
    // }
    public function costComponents()
{
    return $this->belongsToMany(
        \App\Models\CostComponent::class,
        'component_scenario_rules',   // pivot table
        'scenario_id',                // FK ke scenario
        'cost_component_id'           // FK ke cost_component
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
     * Relasi ke training classes
     */
    public function trainingClasses()
    {
        return $this->hasMany(TrainingClass::class);
    }

    /**
     * Get required components untuk scenario ini
     */
    public function getRequiredComponents()
    {
        return $this->costComponents()
            ->wherePivot('is_required', true)
            ->get();
    }

    /**
     * Get optional components untuk scenario ini
     */
    public function getOptionalComponents()
    {
        return $this->costComponents()
            ->wherePivot('is_required', false)
            ->get();
    }

    /**
     * Scope untuk scenario aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk offline scenarios
     */
    public function scopeOffline($query)
    {
        return $query->where('type', 'offline');
    }

    /**
     * Scope untuk online scenarios
     */
    public function scopeOnline($query)
    {
        return $query->where('type', 'online');
    }

    
}
