<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'scenario_id',
        'sales_name',
        'material',
        'customer',
        'training_days',
        'admin_days',
        'participant_count',
        'price_per_participant',
        'discount',
        'price_after_discount',
        'total_revenue',
        'real_revenue',
        'total_cost',
        'net_profit',
        'net_profit_margin',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'training_days' => 'integer',
        'admin_days' => 'integer',
        'participant_count' => 'integer',
        'price_per_participant' => 'decimal:2',
        'discount' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'real_revenue' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'net_profit_margin' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Relasi ke scenario
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * Relasi ke class cost details
     */
    public function costDetails(): HasMany
    {
        return $this->hasMany(ClassCostDetail::class, 'training_class_id');
    }

    /**
     * Calculate total real cost
     */
    public function calculateRealCost()
    {
        return $this->costDetails()
            ->whereHas('costComponent', function($query) {
                $query->where('nature', 'R');
            })
            ->sum('subtotal');
    }

    /**
     * Calculate total pass cost
     */
    public function calculatePassCost()
    {
        return $this->costDetails()
            ->whereHas('costComponent', function($query) {
                $query->where('nature', 'L');
            })
            ->sum('subtotal');
    }

    /**
     * Recalculate semua nilai
     */
    public function recalculate()
    {
        $this->price_after_discount = $this->price_per_participant - $this->discount;
        $this->total_revenue = $this->price_after_discount * $this->participant_count;
        $this->real_revenue = $this->price_per_participant * $this->participant_count;
        $this->total_cost = $this->calculateRealCost() + $this->calculatePassCost();
        $this->net_profit = $this->real_revenue - $this->total_cost;
        
        if ($this->real_revenue > 0) {
            $this->net_profit_margin = ($this->net_profit / $this->real_revenue) * 100;
        }
        
        $this->save();
    }

    /**
     * Scope untuk status tertentu
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
