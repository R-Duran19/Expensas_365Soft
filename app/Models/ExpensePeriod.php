<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpensePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'period_date',
        'status',
        'total_generated',
        'total_collected',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'total_generated' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    // Relaciones
    public function propertyExpenses(): HasMany
    {
        return $this->hasMany(PropertyExpense::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Helpers
    public function getPeriodName(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $months[$this->month] . ' ' . $this->year;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}