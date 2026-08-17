<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ReversePaymentRequest;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with([
            'debt.invoice.customer',
        ])
            ->latest('paid_at')
            ->get();

        $operations = collect();

        $groupedPayments = $payments->groupBy(
            fn($payment) => $payment->payment_group_id ?? 'single_' . $payment->id
        );

        foreach ($groupedPayments as $group) {

            $firstPayment = $group->first();

            if ($firstPayment->payment_group_id) {
                // Pay All
                $operations->push([
                    'type' => 'pay_all',
                    'payment_group_id' => $firstPayment->payment_group_id,

                    'customer' => new \App\Http\Resources\CustomerResource(
                        $firstPayment->debt->invoice->customer
                    ),

                    'total_amount' => (float) $group->sum('amount'),

                    'method' => $firstPayment->method,

                    'paid_at' => $firstPayment->paid_at,

                    'payments' => PaymentResource::collection($group),
                ]);
            } else {
                // Single payment
                $operations->push([
                    'type' => 'single',
                    'payment_group_id' => null,

                    'customer' => new \App\Http\Resources\CustomerResource(
                        $firstPayment->debt->invoice->customer
                    ),

                    'total_amount' => (float) $firstPayment->amount,

                    'method' => $firstPayment->method,

                    'paid_at' => $firstPayment->paid_at,

                    'payments' => PaymentResource::collection($group),
                ]);
            }
        }

        return response()->json([
            'data' => $operations->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $payment = DB::transaction(function () use ($validated, $request) {

            $debt = Debt::with('invoice')
                ->whereKey($validated['debt_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($debt->remaining_amount <= 0) {
                throw ValidationException::withMessages([
                    'debt_id' => ['هذا الدين مسدد بالكامل.'],
                ]);
            }

            if ($validated['amount'] > $debt->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'مبلغ الدفعة أكبر من المبلغ المتبقي: '
                            . number_format($debt->remaining_amount, 2)
                    ],
                ]);
            }

            $payment = $debt->payments()->create([
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? 'cash',
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $debt->remaining_amount -= $validated['amount'];

            $debt->status = $debt->remaining_amount <= 0
                ? 'paid'
                : 'partially_paid';

            $debt->save();

            return $payment;
        });

        return new PaymentResource($payment->load('debt'));
    }
    public function is_reverse(ReversePaymentRequest $request, Payment $payment)
    {
        DB::transaction(function () use ($request, $payment) {

            $payment->load('debt');

            if ($payment->is_reversed) {
                throw ValidationException::withMessages([
                    'payment' => ['هذه الدفعة ملغاة بالفعل.'],
                ]);
            }

            $debt = $payment->debt()
                ->lockForUpdate()
                ->firstOrFail();

            $debt->remaining_amount += $payment->amount;

            $debt->status = $debt->remaining_amount >= $debt->amount
                ? 'unpaid'
                : 'partially_paid';

            $debt->save();

            $payment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $request->validated('reason'),
            ]);
        });

        return response()->json([
            'message' => 'تم إلغاء الدفعة وإعادة المبلغ إلى الدين بنجاح.',
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function reverse(ReversePaymentRequest $request, Payment $payment)
    {
        if ($payment->is_reversed) {
            return response()->json(['message' => 'هذه الدفعة ملغاة مسبقًا'], 422);
        }

        $updatedPayment = DB::transaction(function () use ($request, $payment) {
            // اقفل صف الدين قبل أي قراءة/تعديل
            $debt = Debt::where('id', $payment->debt_id)->lockForUpdate()->firstOrFail();

            $payment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $request->reason,
            ]);

            // رجّع المبلغ للرصيد المتبقي
            $debt->remaining_amount += $payment->amount;
            $debt->status = $debt->remaining_amount >= $debt->amount
                ? 'unpaid'
                : ($debt->remaining_amount > 0 && $debt->remaining_amount < $debt->amount ? 'partially_paid' : 'unpaid');
            $debt->save();

            return $payment;
        });

        return response()->json([
            'message' => 'تم إلغاء الدفعة بنجاح',
            'payment' => new PaymentResource($updatedPayment->load('debt.customer')),
        ]);
    }
}
