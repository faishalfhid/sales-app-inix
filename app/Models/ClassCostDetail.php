<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCostDetail extends Model
{
    protected $fillable = [
        'training_class_id',
        'cost_component_id',
        'period',
        'unit',
        'quantity',
        'unit_cost',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'period' => 'integer',
        'unit' => 'integer',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'training_class_id');
    }

    public function costComponent(): BelongsTo
    {
        return $this->belongsTo(CostComponent::class, 'cost_component_id');
    }

    // Auto calculate subtotal saat saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->subtotal = ($model->period ?? 0) * 
                             ($model->unit ?? 0) * 
                             ($model->quantity ?? 0) * 
                             ($model->unit_cost ?? 0);
        });
    }
}
