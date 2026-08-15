<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Debt;
use App\Http\Resources\DebtResource;

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
