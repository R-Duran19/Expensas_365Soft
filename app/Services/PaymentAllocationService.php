<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PropertyExpense;
use App\Models\PaymentAllocation;
use App\Models\CashTransaction;
use App\Models\TransactionType;
use App\Models\ExpensePeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentAllocationService
{
    /**
     * Distribuir un pago entre las expensas pendientes del propietario
     * Lógica: FIFO (First In, First Out) - expensas más antiguas primero
     */
    public function allocatePayment(Payment $payment): array
    {
        $results = [
            'success' => false,
            'allocated_amount' => 0,
            'remaining_amount' => $payment->amount,
            'allocations' => [],
            'errors' => [],
            'message' => ''
        ];

        try {
            DB::beginTransaction();

            // Validar que el pago esté activo
            if ($payment->status !== 'active') {
                throw new \Exception('El pago no está activo para ser procesado');
            }

            // Obtener expensas pendientes del propietario (más antiguas primero)
            $pendingExpenses = PropertyExpense::where('propietario_id', $payment->propietario_id)
                ->where('status', '!=', 'paid')
                ->where('balance', '>', 0)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->with(['expensePeriod'])
                ->lockForUpdate()
                ->get();

            if ($pendingExpenses->isEmpty()) {
                throw new \Exception('El propietario no tiene expensas pendientes');
            }

            $amountToAllocate = $payment->amount;
            $totalAllocated = 0;

            foreach ($pendingExpenses as $expense) {
                if ($amountToAllocate <= 0) {
                    break;
                }

                $currentBalance = $expense->balance;
                $amountToApply = min($amountToAllocate, $currentBalance);

                // Crear allocation
                $allocation = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'property_expense_id' => $expense->id,
                    'amount' => $amountToApply
                ]);

                // Actualizar expensa
                $newPaidAmount = $expense->paid_amount + $amountToApply;
                $newBalance = $expense->balance - $amountToApply;

                $expense->update([
                    'paid_amount' => $newPaidAmount,
                    'balance' => $newBalance
                ]);

                // Actualizar estado de la expensa
                $expense->updateStatus();

                $totalAllocated += $amountToApply;
                $amountToAllocate -= $amountToApply;

                $results['allocations'][] = [
                    'expense_id' => $expense->id,
                    'period' => $expense->expensePeriod->year . '-' . str_pad($expense->expensePeriod->month, 2, '0', STR_PAD_LEFT),
                    'allocated_amount' => $amountToApply,
                    'previous_balance' => $currentBalance,
                    'new_balance' => $expense->balance,
                    'status' => $expense->status
                ];

                Log::info("Pago {$payment->id} - Allocation: {$amountToApply} BS a expensa {$expense->id} | Balance anterior: {$currentBalance} BS -> Balance nuevo: {$expense->balance} BS");
            }

            // Crear movimiento de caja
            $this->createCashTransaction($payment, $totalAllocated);

            // Obtener los períodos afectados para actualizar total_collected
            $affectedPeriods = $pendingExpenses->pluck('expense_period_id')->unique();
            $periodsUpdated = [];

            foreach ($affectedPeriods as $periodId) {
                $period = ExpensePeriod::find($periodId);
                if ($period) {
                    $allocatedForPeriod = $pendingExpenses
                        ->where('expense_period_id', $periodId)
                        ->sum(function ($expense) {
                            return $expense->payment_allocations()
                                ->where('payment_id', $payment->id)
                                ->sum('amount');
                        });

                    if ($period->addToTotalCollected($allocatedForPeriod)) {
                        $periodsUpdated[] = [
                            'period_id' => $period->id,
                            'period_name' => $period->getPeriodName(),
                            'amount_added' => $allocatedForPeriod,
                            'total_collected' => $period->total_collected,
                            'collection_percentage' => $period->getCollectionPercentage()
                        ];
                    }
                }
            }

            $results['success'] = true;
            $results['allocated_amount'] = $totalAllocated;
            $results['remaining_amount'] = max(0, $amountToAllocate);
            $results['message'] = "Pago procesado exitosamente. Se imputaron {$totalAllocated} BS a expensas pendientes.";

            // Si sobró dinero, crear nota de saldo a favor
            if ($amountToAllocate > 0) {
                $results['message'] .= " Saldo a favor: {$amountToAllocate} BS.";
                $results['has_credit'] = true;
                $results['credit_amount'] = $amountToAllocate;
            }

            // Agregar información de los períodos actualizados
            if (!empty($periodsUpdated)) {
                $results['periods_updated'] = $periodsUpdated;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['message'] = 'Error procesando el pago: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();

            Log::error("Error en PaymentAllocationService::allocatePayment: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Distribuir un pago solo para expensas de un período específico
     */
    public function allocatePaymentToPeriod(Payment $payment, int $expensePeriodId): array
    {
        $results = [
            'success' => false,
            'allocated_amount' => 0,
            'remaining_amount' => $payment->amount,
            'allocations' => [],
            'errors' => [],
            'message' => ''
        ];

        try {
            DB::beginTransaction();

            // Validar que el pago esté activo
            if ($payment->status !== 'active') {
                throw new \Exception('El pago no está activo para ser procesado');
            }

            // Obtener expensa del propietario en el período específico
            $expense = PropertyExpense::where('propietario_id', $payment->propietario_id)
                ->where('expense_period_id', $expensePeriodId)
                ->where('status', '!=', 'paid')
                ->where('balance', '>', 0)
                ->with(['expensePeriod', 'propiedad'])
                ->lockForUpdate()
                ->first();

            if (!$expense) {
                throw new \Exception('El propietario no tiene expensas pendientes en el período seleccionado');
            }

            $amountToAllocate = $payment->amount;
            $currentBalance = $expense->balance;
            $amountToApply = min($amountToAllocate, $currentBalance);

            // Crear allocation
            $allocation = PaymentAllocation::create([
                'payment_id' => $payment->id,
                'property_expense_id' => $expense->id,
                'amount' => $amountToApply
            ]);

            // Actualizar expensa
            $newPaidAmount = $expense->paid_amount + $amountToApply;
            $newBalance = max(0, $expense->total_amount - $newPaidAmount); // Corregir: calcular balance desde cero

            $expense->update([
                'paid_amount' => $newPaidAmount,
                'balance' => $newBalance
            ]);

            // Actualizar estado de la expensa
            $expense->updateStatus();

            // Determinar el estado correctamente según el saldo
            $status = $newBalance <= 0 ? 'paid' :
                     ($newPaidAmount > 0 ? 'partial' : 'pending');

            // Crear movimiento de caja
            $this->createCashTransaction($payment, $amountToApply);

            // Actualizar total_collected del período
            $expensePeriod = $expense->expensePeriod;
            if ($expensePeriod) {
                $periodUpdated = $expensePeriod->addToTotalCollected($amountToApply);
                if (!$periodUpdated) {
                    Log::warning("No se pudo actualizar total_collected para el período {$expensePeriod->id}");
                }
            }

            $results['success'] = true;
            $results['allocated_amount'] = $amountToApply;
            $results['remaining_amount'] = max(0, $amountToAllocate - $amountToApply);
            $results['allocations'][] = [
                'expense_id' => $expense->id,
                'period' => $expense->expensePeriod->year . '-' . str_pad($expense->expensePeriod->month, 2, '0', STR_PAD_LEFT),
                'allocated_amount' => $amountToApply,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'status' => $status
            ];

            $results['message'] = "Pago procesado exitosamente. Se imputaron {$amountToApply} BS a la expensa del período.";

            // Si sobró dinero, crear nota de saldo a favor
            if ($amountToAllocate > $amountToApply) {
                $remainingAmount = $amountToAllocate - $amountToApply;
                $results['message'] .= " Saldo a favor: {$remainingAmount} BS.";
                $results['has_credit'] = true;
                $results['credit_amount'] = $remainingAmount;
            }

            // Agregar información del período al resultado
            if ($expensePeriod) {
                $results['period_summary'] = [
                    'period_id' => $expensePeriod->id,
                    'period_name' => $expensePeriod->getPeriodName(),
                    'total_generated' => $expensePeriod->total_generated,
                    'total_collected_before' => $expensePeriod->total_collected - $amountToApply,
                    'total_collected_after' => $expensePeriod->total_collected,
                    'collection_percentage' => $expensePeriod->getCollectionPercentage(),
                    'pending_amount' => $expensePeriod->getPendingAmount()
                ];
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['message'] = 'Error procesando el pago: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();

            Log::error("Error en PaymentAllocationService::allocatePaymentToPeriod: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Obtener resumen de deudas para un propietario
     */
    public function getOwnerDebtSummary(int $propietarioId): array
    {
        try {
            $pendingExpenses = PropertyExpense::where('propietario_id', $propietarioId)
                ->where('status', '!=', 'paid')
                ->where('balance', '>', 0)
                ->with(['expensePeriod', 'propiedad'])
                ->orderBy('created_at', 'asc')
                ->get();

            $totalDebt = $pendingExpenses->sum('balance');
            $expensesCount = $pendingExpenses->count();

            $expensesDetails = $pendingExpenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'period' => $expense->expensePeriod->year . '-' . str_pad($expense->expensePeriod->month, 2, '0', STR_PAD_LEFT),
                    'property_code' => $expense->propiedad ? $expense->propiedad->codigo : 'N/A',
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $expense->paid_amount,
                    'balance' => $expense->balance,
                    'status' => $expense->status,
                    'due_date' => $expense->due_date ? $expense->due_date->format('d/m/Y') : 'N/A'
                ];
            });

            return [
                'success' => true,
                'total_debt' => $totalDebt,
                'expenses_count' => $expensesCount,
                'expenses' => $expensesDetails,
                'has_debt' => $totalDebt > 0
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo resumen de deudas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Previsualizar cómo se distribuiría un pago
     */
    public function previewAllocation(int $propietarioId, float $paymentAmount): array
    {
        try {
            $pendingExpenses = PropertyExpense::where('propietario_id', $propietarioId)
                ->where('status', '!=', 'paid')
                ->where('balance', '>', 0)
                ->with(['expensePeriod', 'propiedad'])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($pendingExpenses->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'El propietario no tiene expensas pendientes'
                ];
            }

            $amountToAllocate = $paymentAmount;
            $totalAllocated = 0;
            $allocations = [];

            foreach ($pendingExpenses as $expense) {
                if ($amountToAllocate <= 0) {
                    break;
                }

                $currentBalance = $expense->balance;
                $amountToApply = min($amountToAllocate, $currentBalance);

                $allocations[] = [
                    'expense_id' => $expense->id,
                    'period' => $expense->expensePeriod->year . '-' . str_pad($expense->expensePeriod->month, 2, '0', STR_PAD_LEFT),
                    'property_code' => $expense->propiedad ? $expense->propiedad->codigo : 'N/A',
                    'current_balance' => $currentBalance,
                    'allocated_amount' => $amountToApply,
                    'remaining_balance' => $currentBalance - $amountToApply,
                    'will_be_paid' => ($currentBalance - $amountToApply) <= 0
                ];

                $totalAllocated += $amountToApply;
                $amountToAllocate -= $amountToApply;
            }

            return [
                'success' => true,
                'payment_amount' => $paymentAmount,
                'total_allocated' => $totalAllocated,
                'remaining_amount' => max(0, $amountToAllocate),
                'allocations' => $allocations,
                'expenses_covered' => count($allocations),
                'has_credit' => $amountToAllocate > 0,
                'credit_amount' => max(0, $amountToAllocate)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en previsualización: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revertir allocations de un pago (para anulación)
     */
    public function reversePaymentAllocation(Payment $payment): array
    {
        $results = [
            'success' => false,
            'reversed_amount' => 0,
            'affected_expenses' => [],
            'errors' => [],
            'message' => ''
        ];

        try {
            DB::beginTransaction();

            if ($payment->status !== 'active') {
                throw new \Exception('El pago ya está anulado');
            }

            $allocations = PaymentAllocation::where('payment_id', $payment->id)
                ->with('propertyExpense')
                ->get();

            if ($allocations->isEmpty()) {
                throw new \Exception('El pago no tiene imputaciones para revertir');
            }

            $totalReversed = 0;

            foreach ($allocations as $allocation) {
                $expense = $allocation->propertyExpense;

                // Revertir montos en la expensa
                $newPaidAmount = $expense->paid_amount - $allocation->amount;
                $newBalance = $expense->balance + $allocation->amount;

                $expense->update([
                    'paid_amount' => $newPaidAmount,
                    'balance' => $newBalance
                ]);

                // Actualizar estado
                $expense->updateStatus();

                $results['affected_expenses'][] = [
                    'expense_id' => $expense->id,
                    'reversed_amount' => $allocation->amount,
                    'new_balance' => $expense->balance,
                    'new_status' => $expense->status
                ];

                $totalReversed += $allocation->amount;

                // Eliminar allocation
                $allocation->delete();
            }

            // Marcar pago como cancelado
            $payment->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => 'Reversión manual de imputaciones'
            ]);

            // Crear movimiento de caja negativo
            $this->createCashTransactionReversal($payment, $totalReversed);

            $results['success'] = true;
            $results['reversed_amount'] = $totalReversed;
            $results['message'] = "Se reversieron {$totalReversed} BS en imputaciones exitosamente.";

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['message'] = 'Error revirtiendo pago: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();

            Log::error("Error en PaymentAllocationService::reversePaymentAllocation: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Crear movimiento de caja para un pago
     */
    private function createCashTransaction(Payment $payment, float $amount): void
    {
        try {
            // Buscar tipo de transacción "Ingreso por Expensa"
            $transactionType = TransactionType::where('code', 'ING_EXPENSA')
                ->where('type', 'income')
                ->where('is_active', true)
                ->first();

            if (!$transactionType) {
                Log::warning("No se encontró tipo de transacción ING_EXPENSA, usando tipo genérico");
                $transactionType = TransactionType::where('type', 'income')
                    ->where('is_active', true)
                    ->first();
            }

            if ($transactionType) {
                CashTransaction::create([
                    'expense_period_id' => null, // No asociado a período específico
                    'transaction_type_id' => $transactionType->id,
                    'payment_id' => $payment->id,
                    'type' => 'income',
                    'amount' => $amount,
                    'transaction_date' => $payment->payment_date,
                    'description' => "Pago de expensas - Recibo: {$payment->receipt_number}",
                    'reference' => $payment->reference
                ]);

                Log::info("Movimiento de caja creado: {$amount} BS para pago {$payment->id}");
            } else {
                Log::error("No se pudo crear movimiento de caja: no hay tipos de transacción de ingresos activos");
            }

        } catch (\Exception $e) {
            Log::error("Error creando movimiento de caja para pago {$payment->id}: " . $e->getMessage());
        }
    }

    /**
     * Crear movimiento de caja de reversión
     */
    private function createCashTransactionReversal(Payment $payment, float $amount): void
    {
        try {
            // Buscar tipo de transacción para egresos/reversiones
            $transactionType = TransactionType::where('type', 'expense')
                ->where('is_active', true)
                ->first();

            if ($transactionType) {
                CashTransaction::create([
                    'expense_period_id' => null,
                    'transaction_type_id' => $transactionType->id,
                    'payment_id' => $payment->id,
                    'type' => 'expense',
                    'amount' => $amount,
                    'transaction_date' => now(),
                    'description' => "Anulación de pago - Recibo: {$payment->receipt_number}",
                    'reference' => "REVERSIÓN-{$payment->receipt_number}"
                ]);

                Log::info("Movimiento de caja de reversión creado: {$amount} BS para pago {$payment->id}");
            } else {
                Log::error("No se pudo crear movimiento de caja de reversión: no hay tipos de transacción de egresos activos");
            }

        } catch (\Exception $e) {
            Log::error("Error creando movimiento de caja de reversión para pago {$payment->id}: " . $e->getMessage());
        }
    }
}