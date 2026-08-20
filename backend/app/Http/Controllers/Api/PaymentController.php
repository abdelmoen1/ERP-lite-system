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
    public function index(Request $request)
    {
        $storeId = $request->user()->store_id;

        $payments = Payment::forStore($storeId)
            ->with([
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
        $storeId = $request->user()->store_id;

        $payment = DB::transaction(function () use ($validated, $request, $storeId) {

            $debt = Debt::with('invoice')
                ->whereKey($validated['debt_id'])
                ->where('store_id', $storeId)
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
        $this->ensurePaymentBelongsToStore($request, $payment);

        DB::transaction(function () use ($request, $payment) {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->is_reversed) {
                throw ValidationException::withMessages([
                    'payment' => ['هذه الدفعة ملغاة بالفعل.'],
                ]);
            }

            $debt = Debt::query()
                ->whereKey($lockedPayment->debt_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment->update([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $request->validated('reason'),
            ]);

            $paidAmount = $debt->payments()
                ->where('is_reversed', false)
                ->sum('amount');
            $debt->remaining_amount = max(0, (float) $debt->amount - $paidAmount);
            $debt->status = $debt->remaining_amount <= 0
                ? 'paid'
                : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');
            $debt->save();
        });

        return response()->json([
            'message' => 'تم إلغاء الدفعة وإعادة المبلغ إلى الدين بنجاح.',
        ]);
    }

    public function reverseGroup(ReversePaymentGroupRequest $request, string $paymentGroupId)
    {
        $storeId = $request->user()->store_id;

        $result = DB::transaction(function () use ($request, $paymentGroupId, $storeId) {

            $payments = Payment::where('payment_group_id', $paymentGroupId)
                ->where('is_reversed', false)
                ->forStore($storeId)
                ->lockForUpdate()
                ->get();

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'payment_group_id' => [
                        'عملية السداد غير موجودة أو تم إلغاؤها بالفعل.'
                    ],
                ]);
            }

            $debts = Debt::query()
                ->whereIn('id', $payments->pluck('debt_id'))
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalReversed = 0;
            $debtsCount = 0;

            foreach ($payments as $payment) {
                $debt = $debts->get($payment->debt_id);

                $payment->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $request->validated('reason'),
                ]);

                $paidAmount = $debt->payments()
                    ->where('is_reversed', false)
                    ->sum('amount');
                $debt->remaining_amount = max(0, (float) $debt->amount - $paidAmount);
                $debt->status = $debt->remaining_amount <= 0
                    ? 'paid'
                    : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');
                $debt->save();

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

    private function ensurePaymentBelongsToStore(Request $request, Payment $payment): void
    {
        $payment->loadMissing('debt');

        abort_unless(
            $payment->debt?->store_id === $request->user()->store_id,
            404
        );
    }
}
