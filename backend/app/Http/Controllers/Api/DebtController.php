<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Http\Requests\UpdateDebtRequest;
use App\Http\Resources\DebtResource;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\InvoiceResource;

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
    public function store(StorePaymentRequest $request)
    {
        //
    }
    public function invoice(Debt $debt)
    {
        $invoice = $debt->invoice()->with([
            'customer',
            'items',
        ])->firstOrFail();

        return new InvoiceResource($invoice);
    }

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

        if (isset($data['amount']) && (float)$data['amount'] !== (float)$debt->amount) {

            if ($debt->payments()->exits()) {
                return response()->json([
                    'message' => 'لا يمكن تحديث المبلغ لأن الدين يتضمن دفعات.'
                ], 422);
            }

            $data['remaining_amount'] = $data['amount'];
        }

        $debt->update($data);

        return new DebtResource($debt->load('customer'));
    }

    public function destroy(Debt $debt)
    {
        if ($debt->payments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف دين له دفعات مسجّلة',
            ], 422);
        }

        $debt->delete();
        return response()->json(['message' => 'تم حذف الدين بنجاح']);
    }
}
