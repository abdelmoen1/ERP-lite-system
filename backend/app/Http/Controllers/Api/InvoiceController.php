<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Enums\InvoiceSource;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $invoices = Invoice::with([
            'customer',
            'items',
            'debt.payments',
        ])
            ->where('store_id', $request->user()->store_id)
            ->latest()
            ->paginate(10);

        return InvoiceResource::collection($invoices);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $storeId = $request->user()->store_id;

        // Make sure the selected customer belongs to the current store
        $customerExists = \App\Models\Customer::query()
            ->where('id', $validated['customer_id'])
            ->where('store_id', $storeId)
            ->exists();

        if (!$customerExists) {
            return response()->json([
                'message' => 'العميل غير موجود أو لا ينتمي إلى متجرك.',
            ], 404);
        }

        $totalAmount = collect($validated['items'])->sum(function ($item) {
            return round($item['quantity'] * (float) $item['unit_price'], 2);
        });

        try {
            $invoice = DB::transaction(function () use (
                $validated,
                $totalAmount,
                $storeId
            ) {
                $invoice = Invoice::create([
                    'store_id' => $storeId,
                    'customer_id' => $validated['customer_id'],
                    'has_debt' => $validated['has_debt'],
                    'payment_method' => $validated['has_debt']
                        ? null
                        : $validated['payment_method'],
                    'total_amount' => $totalAmount,
                ]);

                foreach ($validated['items'] as $item) {
                    $invoice->items()->create([
                        'item_name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => round($item['quantity'] * (float) $item['unit_price'], 2),
                    ]);
                }

                if ($validated['has_debt'] === true) {
                    Debt::create([
                        'invoice_id' => $invoice->id,
                        'store_id' => $storeId,
                        'amount' => $totalAmount,
                        'remaining_amount' => $totalAmount,
                        'status' => 'unpaid',
                    ]);
                }

                return $invoice;
            });

            return response()->json([
                'message' => 'تم حفظ الفاتورة'
                    . ($validated['has_debt'] ? ' وتسجيل الدين' : '')
                    . ' بنجاح.',
                'data' => new InvoiceResource(
                    $invoice->load(['customer', 'items', 'debt.payments'])
                ),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حفظ الفاتورة، يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Invoice $invoice)
    {
        abort_unless(
            $invoice->store_id === $request->user()->store_id,
            404
        );

        $invoice->load([
            'customer',
            'items',
            'debt.payments',
        ]);

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        abort_unless(
            $invoice->store_id === $request->user()->store_id,
            404
        );

        return response()->json([
            'message' => 'تعديل الفواتير غير متاح حاليًا.',
        ], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Invoice $invoice)
    {
        abort_unless(
            $invoice->store_id === $request->user()->store_id,
            404
        );

        return response()->json([
            'message' => 'حذف الفواتير غير متاح حاليًا.',
        ], 405);
    }
}
