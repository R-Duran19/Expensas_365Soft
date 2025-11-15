<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'water_factor_id',
        'expense_period_id',
        'propiedad_id',
        'propietario_id',
        'inquilino_id',
        'facturar_a',
        'base_amount',
        'water_amount',
        'other_amount',
        'previous_debt',
        'total_amount',
        'paid_amount',
        'balance',
        'water_previous_reading',
        'water_current_reading',
        'water_consumption',
        'water_factor',
        'status',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'water_amount' => 'integer',
        'other_amount' => 'integer',
        'previous_debt' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'decimal:2',
        'balance' => 'integer',
        'water_previous_reading' => 'decimal:2',
        'water_current_reading' => 'decimal:2',
        'water_consumption' => 'decimal:2',
        'water_factor' => 'decimal:6',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    // Relaciones
    public function waterFactor(): BelongsTo
    {
        return $this->belongsTo(WaterFactor::class);
    }

    public function expensePeriod(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class);
    }

    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    public function inquilino(): BelongsTo
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PropertyExpenseDetail::class);
    }

    // Mantener la relación anterior si existe
    public function expenseDetails(): HasMany
    {
        return $this->hasMany(PropertyExpenseDetail::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeWithDebt($query)
    {
        return $query->where('balance', '>', 0);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function hasDebt(): bool
    {
        return $this->balance > 0;
    }

    public function updateStatus(): void
    {
        if ($this->balance <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }
    public function getResponsablePago()
    {
        return $this->facturar_a === 'inquilino' && $this->inquilino
            ? $this->inquilino
            : $this->propietario;
    }
}
