<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Debt;
use App\Http\Resources\DebtResource;
use App\Http\Requests\PayAllDebtsRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CustomerDebtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Customer $customer)
    {
        $query = Debt::whereHas('invoice', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        });

        $debts = (clone $query)
            ->with(['invoice', 'payments'])
            ->latest()
            ->get();

        $summary = [
            'debts_count' => $debts->count(),
            'total_debt' => (float) $debts->sum('amount'),
            'total_payments' => (float) $debts->sum(
                fn($debts) => (float) $debts->payments->sum('amount')
            ),
            'total_remaining' => (float) $debts->sum('remaining_amount'),
        ];

        return response()->json([
            'data' => DebtResource::collection($debts),
            'summary' => $summary,
        ]);
    }

    public function payAll(PayAllDebtsRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated, $customer, $request) {

            $debts = $customer->debts()
                ->where('remaining_amount', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($debts->isEmpty()) {
                throw ValidationException::withMessages([
                    'customer' => ['لا يوجد على هذا العميل أي مبلغ متبقي.'],
                ]);
            }

            $paymentGroupId = (string) Str::uuid();

            $totalPaid = 0;

            foreach ($debts as $debt) {
                $amount = $debt->remaining_amount;

                $debt->payments()->create([
                    'amount' => $amount,
                    'method' => $validated['method'],
                    'paid_at' => $validated['paid_at'] ?? now(),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()?->id,
                    'payment_group_id' => $paymentGroupId,

                ]);

                $debt->remaining_amount = 0;
                $debt->status = 'paid';
                $debt->save();

                $totalPaid += $amount;
            }

            return [
                'payment_group_id' => $paymentGroupId,
                'debts_count' => $debts->count(),
                'total_paid' => $totalPaid,
            ];
        });

        return response()->json([
            'message' => 'تم تسديد جميع ديون العميل بنجاح.',
            'data' => $result,
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
}
