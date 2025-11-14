<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'propiedad_id',
        'propietario_id',
        'inquilino_id',
        'pagado_por',
        'payment_type_id',
        'amount',
        'payment_date',
        'registered_at',
        'reference',
        'notes',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'registered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relaciones
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

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function cashTransaction(): HasOne
    {
        return $this->hasOne(CashTransaction::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public static function generateReceiptNumber(): string
    {
        $lastPayment = static::orderBy('id', 'desc')->first();
        $number = $lastPayment ? intval(substr($lastPayment->receipt_number, 4)) + 1 : 1;

        return 'REC-' . str_pad($number, 8, '0', STR_PAD_LEFT);
    }

    public function getQuienPago()
    {
        return $this->pagado_por === 'inquilino' && $this->inquilino
            ? $this->inquilino
            : $this->propietario;
    }
}
