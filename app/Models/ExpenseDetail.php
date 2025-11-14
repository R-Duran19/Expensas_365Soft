<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_expense_id',
        'expense_concept_id',
        'amount',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relaciones
    public function propertyExpense(): BelongsTo
    {
        return $this->belongsTo(PropertyExpense::class);
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(ExpenseConcept::class, 'expense_concept_id');
    }
}