<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'expense_period_id',
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

    public function expensePeriod(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class);
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

    /**
     * Obtener pagos de un propietario en un período específico
     */
    public static function getOwnerPaymentsInPeriod(int $propietarioId, int $expensePeriodId)
    {
        return static::where('propietario_id', $propietarioId)
            ->where('expense_period_id', $expensePeriodId)
            ->where('status', 'active')
            ->with(['paymentType'])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    /**
     * Obtener total pagado por un propietario en un período
     */
    public static function getTotalPaidInPeriod(int $propietarioId, int $expensePeriodId): float
    {
        return static::where('propietario_id', $propietarioId)
            ->where('expense_period_id', $expensePeriodId)
            ->where('status', 'active')
            ->sum('amount');
    }

    /**
     * Obtener créditos disponibles de un propietario hasta un período específico
     */
    public static function getAvailableCreditUntilPeriod(int $propietarioId, int $expensePeriodId): float
    {
        try {
            // Obtener período actual
            $currentPeriod = ExpensePeriod::find($expensePeriodId);
            if (!$currentPeriod) {
                return 0;
            }

            // Calcular créditos de períodos anteriores usando join directo
            $totalPaid = static::where('propietario_id', $propietarioId)
                ->where('status', 'active')
                ->join('expense_periods', 'payments.expense_period_id', '=', 'expense_periods.id')
                ->where(function ($query) use ($currentPeriod) {
                    $query->where('expense_periods.year', '<', $currentPeriod->year)
                          ->orWhere(function ($subQuery) use ($currentPeriod) {
                              $subQuery->where('expense_periods.year', $currentPeriod->year)
                                       ->where('expense_periods.month', '<', $currentPeriod->month);
                          });
                })
                ->sum('payments.amount');

            // Obtener total de expensas de esos mismos períodos usando join directo
            $totalExpenses = DB::table('property_expenses')
                ->join('expense_periods', 'property_expenses.expense_period_id', '=', 'expense_periods.id')
                ->where('property_expenses.propietario_id', $propietarioId)
                ->where(function ($query) use ($currentPeriod) {
                    $query->where('expense_periods.year', '<', $currentPeriod->year)
                          ->orWhere(function ($subQuery) use ($currentPeriod) {
                              $subQuery->where('expense_periods.year', $currentPeriod->year)
                                       ->where('expense_periods.month', '<', $currentPeriod->month);
                          });
                })
                ->sum('property_expenses.total_amount');

            $availableCredit = $totalPaid - $totalExpenses;

            Log::info("Cálculo de crédito para propietario {$propietarioId} hasta período {$expensePeriodId}:");
            Log::info("  Total pagado períodos anteriores: {$totalPaid} BS");
            Log::info("  Total expensas períodos anteriores: {$totalExpenses} BS");
            Log::info("  Crédito disponible: {$availableCredit} BS");

            return max(0, $availableCredit);
        } catch (\Exception $e) {
            Log::error("Error calculando crédito para propietario {$propietarioId}: " . $e->getMessage());
            return 0; // Retornar 0 en caso de error para no detener el proceso
        }
    }
}
