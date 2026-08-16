<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Invoice;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoice::with(['customer', 'items', 'debt'])->get();

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $totalAmount = collect($validated['items'])->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        try {
            $invoice = DB::transaction(function () use ($validated, $totalAmount) {

                $invoice = Invoice::create([
                    'customer_id' => $validated['customer_id'],
                    'has_debt' => $validated['has_debt'],
                    'payment_method' => $validated['has_debt'] ? null : $validated['payment_method'],
                    'total_amount' => $totalAmount,
                ]);
                $invoiceItems = [];
                foreach ($validated['items'] as $item) {
                    $invoiceItems[] = [
                        'invoice_id' => $invoice->id,
                        'item_name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['quantity'] * $item['unit_price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $invoice->items()->createMany($invoiceItems);

                if ($validated['has_debt'] === true) {
                    Debt::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $totalAmount,
                        'remaining_amount' => $totalAmount,
                    ]);
                }
                return $invoice;
            });
            return response()->json([
                'message' => 'تم حفظ الفاتورة ' . ($validated['has_debt'] ? 'وتسجيل الدين ' : '') . 'بنجاح.',
                'data' => $invoice->load('items')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حفظ الفاتورة، يرجى المحاولة لاحقاً.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items',
            'debt',
        ]);
        return new InvoiceResource($invoice);
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
