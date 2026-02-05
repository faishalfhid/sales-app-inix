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
        'sales_id',
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
        'approval_notes',
        'approved_by',
        'approved_at',
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
        'approved_at' => 'datetime',
        
    ];
    /* ================= GET SALES ID BY USER ID ================= */
    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }


    /* ================= GET APPROVER ================= */

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ================= RELATIONS ================= */

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function costDetails(): HasMany
    {
        return $this->hasMany(ClassCostDetail::class, 'training_class_id');
    }

    /* ================= COST CALCULATION ================= */

    public function calculateRealCost(): float
    {
        return $this->costDetails
            ->filter(fn($d) => $d->costComponent?->nature === 'R')
            ->sum(fn($d) => ($d->unit ?? 0) * ($d->unit_cost ?? 0));
    }

    public function calculatePassCost(): float
    {
        return $this->costDetails
            ->filter(fn($d) => $d->costComponent?->nature === 'L')
            ->sum(fn($d) => ($d->unit ?? 0) * ($d->unit_cost ?? 0));
    }

    /* ================= MAIN RECALCULATE ================= */

    public function recalculate(): void
    {
        $participant = $this->participant_count ?? 0;
        $price = $this->price_per_participant ?? 0;
        $discount = $this->discount ?? 0;

        $this->real_revenue = $price * $participant;
        $this->price_after_discount = max($price - $discount, 0);
        $this->total_revenue = $this->price_after_discount * $participant;

        $this->total_cost = $this->calculateRealCost() + $this->calculatePassCost();
        $this->net_profit = $this->real_revenue - $this->total_cost;

        $this->net_profit_margin = $this->real_revenue > 0
            ? ($this->net_profit / $this->real_revenue) * 100
            : 0;

        $this->saveQuietly();
    }

    /* ================= SCOPE ================= */

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /* ================= APPROVAL ================= */

    public function canBeApprovedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->canApprove();
    }
}
