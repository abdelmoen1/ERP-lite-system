<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debt;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\UpdateDebtRequest;
use App\Http\Resources\DebtResource;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $debts = Debt::with('customer')->select(
            'id',
            'customer_id',
            'amount',
            'remaining_amount',
            'status',
        )->paginate(10);
        return DebtResource::collection($debts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDebtRequest $request)
    {
        $debt = Debt::create([
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'remaining_amount' => $request->amount,
        ]);

        return response()->json([
            'message' => 'Debt Stored successfully',
            'debt' => new DebtResource($debt->load('customer')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Debt $debt)
    {
        $debt->load('customer');

        return new DebtResource($debt);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDebtRequest $request, Debt $debt)
    {
        $data = $request->validated();

        if (isset($data['amount'])) {

            if ($debt->amount != $debt->remaining_amount) {
                return response()->json([
                    'message' => 'Cannot update amount because debt has payments'
                ], 422);
            }

            $data['remaining_amount'] = $data['amount'];
        }

        $debt->update($data);

        return new DebtResource($debt->load('customer'));
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Debt $debt)
    {
        $debt->delete();

        return response()->json([
            'message' => 'Debt deleted successfully.'
        ]);
    }
}
