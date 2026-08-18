<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Http\Requests\UpdateDebtRequest;
use App\Http\Resources\DebtResource;
use App\Http\Resources\DebtDetailsResource;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $storeId = $request->user()->store_id;

        $debts = Debt::query()
            ->where('store_id', $storeId)
            ->with('invoice')
            ->select(
                'id',
                'store_id',
                'invoice_id',
                'amount',
                'remaining_amount',
                'status',
            )
            ->latest()
            ->paginate(10);

        return DebtResource::collection($debts);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'إنشاء الديون يتم تلقائيًا عند إنشاء فاتورة بالدين.',
        ], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Debt $debt)
    {
        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        $debt->load('invoice.customer');

        return new DebtResource($debt);
    }

    /**
     * Display debt details.
     */
    public function details(Request $request, Debt $debt)
    {
        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        $debt->load([
            'payments',
            'invoice.customer',
            'invoice.items',
        ]);

        return new DebtDetailsResource($debt);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateDebtRequest $request,
        Debt $debt
    ) {
        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        $data = $request->validated();

        if (
            isset($data['amount']) &&
            (float) $data['amount'] !== (float) $debt->amount
        ) {
            if ($debt->payments()->exists()) {
                return response()->json([
                    'message' => 'لا يمكن تحديث المبلغ لأن الدين يتضمن دفعات.',
                ], 422);
            }

            $data['remaining_amount'] = $data['amount'];
        }

        $debt->update($data);

        return new DebtResource(
            $debt->load('invoice.customer')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Debt $debt)
    {
        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        if ($debt->payments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف دين له دفعات مسجّلة',
            ], 422);
        }

        $debt->delete();

        return response()->json([
            'message' => 'تم حذف الدين بنجاح',
        ]);
    }
}
