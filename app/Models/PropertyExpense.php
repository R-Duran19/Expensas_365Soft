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
        'balance' => 'decimal:2',
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

    /**
     * Obtener todos los pagos del propietario (para mostrar información completa)
     */
    public function getOwnerPayments()
    {
        return Payment::where('propietario_id', $this->propietario_id)
            ->where('status', 'active')
            ->with(['paymentType', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Obtener el total depositado por el propietario en todos sus pagos
     */
    public function getTotalDeposited(): float
    {
        return Payment::where('propietario_id', $this->propietario_id)
            ->where('status', 'active')
            ->sum('amount');
    }

    /**
     * Obtener el total de saldos a favor del propietario
     */
    public function getTotalCredit(): float
    {
        $totalDeposited = $this->getTotalDeposited();
        $totalAllocated = PaymentAllocation::whereHas('payment', function ($query) {
            $query->where('propietario_id', $this->propietario_id)
                  ->where('status', 'active');
        })->sum('amount');

        return max(0, $totalDeposited - $totalAllocated);
    }

    /**
     * Obtener resumen completo de pagos para mostrar en vistas
     */
    public function getPaymentSummary(): array
    {
        $payments = $this->getOwnerPayments();
        $totalDeposited = 0;
        $totalAllocated = 0;
        $paymentDetails = [];

        foreach ($payments as $payment) {
            $totalDeposited += $payment->amount;
            $allocatedToThis = $payment->allocations()
                ->where('property_expense_id', $this->id)
                ->sum('amount');
            $totalAllocated += $allocatedToThis;

            $paymentDetails[] = [
                'payment' => $payment,
                'allocated_to_this' => $allocatedToThis,
                'credit_from_this' => $payment->amount - $allocatedToThis,
                'has_credit' => $payment->amount > $allocatedToThis
            ];
        }

        return [
            'total_deposited' => $totalDeposited,
            'total_allocated_to_this' => $totalAllocated,
            'total_credit' => $totalDeposited - $totalAllocated,
            'payment_details' => $paymentDetails,
            'has_credit' => ($totalDeposited - $totalAllocated) > 0
        ];
    }

    /**
     * Obtener pagos del período actual para esta expensa
     */
    public function getCurrentPeriodPayments()
    {
        return Payment::where('propietario_id', $this->propietario_id)
            ->where('expense_period_id', $this->expense_period_id)
            ->where('status', 'active')
            ->with(['paymentType'])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    /**
     * Obtener total pagado en el período actual
     */
    public function getCurrentPeriodPaidAmount(): float
    {
        return Payment::getTotalPaidInPeriod($this->propietario_id, $this->expense_period_id);
    }

    /**
     * Obtener total disponible de crédito para el siguiente período
     */
    public function getCreditForNextPeriod(): float
    {
        $totalPaid = $this->getCurrentPeriodPaidAmount();
        $creditAvailable = $totalPaid - $this->total_amount;

        return max(0, $creditAvailable);
    }

    /**
     * Actualizar estado basado en pagos del período actual
     */
    public function updateStatusFromCurrentPayments(): void
    {
        $totalPaid = $this->getCurrentPeriodPaidAmount();

        if ($totalPaid >= $this->total_amount) {
            $this->status = 'paid';
            $this->paid_amount = $totalPaid;
            $this->balance = 0;
            $this->paid_at = now();
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
            $this->paid_amount = $totalPaid;
            $this->balance = $this->total_amount - $totalPaid;
        } else {
            $this->status = 'pending';
            $this->paid_amount = 0;
            $this->balance = $this->total_amount;
        }

        $this->save();
    }
}
