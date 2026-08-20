<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Resources\DebtResource;
use App\Http\Resources\DebtDetailsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\InvoiceSource;

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
            ->with(['invoice', 'payments'])
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
    public function store(StoreDebtRequest $request)
    {
        $validated = $request->validated();
        $storeId = $request->user()->store_id;

        $invoice = DB::transaction(function () use ($validated, $storeId) {

            $invoice = \App\Models\Invoice::create([
                'store_id' => $storeId,
                'customer_id' => $validated['customer_id'],
                'total_amount' => $validated['amount'],
                'has_debt' => true,
                'payment_method' => null,
                'source' => \App\Enums\InvoiceSource::OPENING_DEBT,
            ]);

            $invoice->items()->create([
                'item_name' => 'دين قديم',
                'quantity' => 1,
                'unit_price' => $validated['amount'],
                'total' => $validated['amount'],
            ]);

            Debt::create([
                'invoice_id' => $invoice->id,
                'store_id' => $storeId,
                'amount' => $validated['amount'],
                'remaining_amount' => $validated['amount'],
                'status' => 'unpaid',
            ]);

            return $invoice;
        });

        return response()->json([
            'message' => 'تم تسجيل الدين القديم بنجاح.',
            'data' => new \App\Http\Resources\InvoiceResource(
                $invoice->load([
                    'customer',
                    'items',
                    'debt.payments',
                ])
            ),
        ], 201);
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
}
