<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentOperationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ReversePaymentRequest;
use App\Http\Requests\ReversePaymentGroupRequest;
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

        $operations = $payments
            ->groupBy(function ($payment) {
                return $payment->payment_group_id
                    ?? 'single_' . $payment->id;
            })
            ->map(function ($group) {

                $firstPayment = $group->first();

                $isPayAll = $firstPayment->payment_group_id !== null;

                $operation = new \stdClass();

                $operation->type = $isPayAll
                    ? 'pay_all'
                    : 'single';

                $operation->payment_group_id =
                    $firstPayment->payment_group_id;

                $operation->customer =
                    $firstPayment->debt?->invoice?->customer;

                $operation->total_amount =
                    (float) $group->sum('amount');

                $operation->method =
                    $firstPayment->method;

                $operation->paid_at =
                    $firstPayment->paid_at;

                $operation->is_reversed =
                    $group->every(fn($payment) => $payment->is_reversed);

                $operation->reversed_at =
                    $operation->is_reversed
                    ? $group->max('reversed_at')
                    : null;

                $operation->reversal_reason =
                    $operation->is_reversed
                    ? $group->first()->reversal_reason
                    : null;

                $operation->payments = $group->values();

                return $operation;
            })
            ->values();

        return PaymentOperationResource::collection($operations);
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

    public function reverseGroup(ReversePaymentGroupRequest $request, string $paymentGroupId)
    {
        $result = DB::transaction(function () use ($request, $paymentGroupId) {

            $payments = Payment::where('payment_group_id', $paymentGroupId)
                ->where('is_reversed', false)
                ->with('debt')
                ->lockForUpdate()
                ->get();

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'payment_group_id' => [
                        'عملية السداد غير موجودة أو تم إلغاؤها بالفعل.'
                    ],
                ]);
            }

            $totalReversed = 0;
            $debtsCount = 0;

            foreach ($payments as $payment) {
                $debt = $payment->debt;

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

                $totalReversed += $payment->amount;
                $debtsCount++;
            }

            return [
                'payment_group_id' => $paymentGroupId,
                'debts_count' => $debtsCount,
                'total_reversed' => $totalReversed,
            ];
        });

        return response()->json([
            'message' => 'تم إلغاء عملية السداد بالكامل بنجاح.',
            'data' => $result,
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
