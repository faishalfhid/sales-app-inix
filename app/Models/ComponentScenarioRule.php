<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentScenarioRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_component_id',
        'scenario_id',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Relasi ke cost component
     */
    public function costComponent()
    {
        return $this->belongsTo(CostComponent::class);
    }

    /**
     * Relasi ke scenario
     */
    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * Scope untuk required rules
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope untuk optional rules
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
