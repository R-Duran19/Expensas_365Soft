<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'property_expense_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relaciones
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function propertyExpense(): BelongsTo
    {
        return $this->belongsTo(PropertyExpense::class);
    }
}