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

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        $payment = DB::transaction(function () use ($request) {
            $debt = Debt::where('id', $request->debt_id)->lockForUpdate()->firstOrFail();

            $totalPaid = $debt->payments()->where('is_reversed', false)->sum('amount');
            $remaining = $debt->original_amount - $totalPaid;

            if ($request->amount > $remaining) {
                abort(422, 'المبلغ المدخل أكبر من الرصيد المتبقي: ' . number_format($remaining, 2));
            }

            $payment = $debt->payments()->create([
                'amount' => $request->amount,
                'method' => $request->method ?? 'cash',
                'paid_at' => $request->paid_at,
                'notes' => $request->notes,
                'created_by' => $request->user()?->id,
            ]);

            $newTotalPaid = $totalPaid + $request->amount;
            $debt->status = $newTotalPaid >= $debt->original_amount
                ? 'paid'
                : ($newTotalPaid > 0 ? 'partially_paid' : 'open');
            $debt->save();

            return $payment;
        });

        return response()->json([
            'message' => 'تم تسجيل الدفعة بنجاح',
            'payment' => new PaymentResource($payment->load('debt.customer')),
        ], 201);
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
