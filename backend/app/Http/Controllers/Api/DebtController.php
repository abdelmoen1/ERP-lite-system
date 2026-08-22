<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\UpdateDebtRequest;
use App\Http\Resources\DebtResource;
use App\Http\Resources\DebtDetailsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\InvoiceSource;
use App\Enums\UserRole;

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

    public function update(
        UpdateDebtRequest $request,
        Debt $debt
    ) {
        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        $validated = $request->validated();

        $updatedDebt = DB::transaction(function () use ($validated, $debt) {

            $lockedDebt = Debt::query()
                ->whereKey($debt->id)
                ->where('store_id', $debt->store_id)
                ->with(['invoice.items'])
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = $lockedDebt->invoice;

            if (!$invoice) {
                abort(422, 'هذا الدين غير مرتبط بفاتورة.');
            }

            if ($invoice->source !== InvoiceSource::OPENING_DEBT) {
                abort(
                    422,
                    'لا يمكن تعديل دين ناتج عن فاتورة من خلال هذا المسار.'
                );
            }

            if ($lockedDebt->payments()->exists()) {
                abort(
                    422,
                    'لا يمكن تعديل الدين بعد تسجيل دفعات عليه.'
                );
            }

            if (array_key_exists('amount', $validated)) {

                $newAmount = round((float) $validated['amount'], 2);

                // Update Debt
                $lockedDebt->amount = $newAmount;
                $lockedDebt->remaining_amount = $newAmount;
                $lockedDebt->status = 'unpaid';
                $lockedDebt->save();

                // Update Invoice
                $invoice->total_amount = $newAmount;
                $invoice->save();

                // Update the internal "دين قديم" invoice item
                $item = $invoice->items
                    ->firstWhere('item_name', 'دين قديم');

                if ($item) {
                    $item->quantity = 1;
                    $item->unit_price = $newAmount;
                    $item->total = $newAmount;
                    $item->save();
                }
            }

            return $lockedDebt->fresh()->load([
                'invoice.customer',
                'invoice.items',
                'payments',
            ]);
        });

        return new DebtResource($updatedDebt);
    }

    public function destroy(
        Request $request,
        Debt $debt
    ) {
        abort_unless(
            $request->user()?->hasRole(UserRole::OWNER, UserRole::MANAGER),
            403
        );

        abort_unless(
            $debt->store_id === $request->user()->store_id,
            404
        );

        DB::transaction(function () use ($debt) {

            $lockedDebt = Debt::query()
                ->whereKey($debt->id)
                ->where('store_id', $debt->store_id)
                ->with('invoice')
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = $lockedDebt->invoice;

            if (!$invoice) {
                abort(422, 'هذا الدين غير مرتبط بفاتورة.');
            }

            if ($invoice->source !== InvoiceSource::OPENING_DEBT) {
                abort(
                    422,
                    'لا يمكن حذف دين ناتج عن فاتورة من خلال هذا المسار.'
                );
            }

            if ($lockedDebt->payments()->exists()) {
                abort(
                    422,
                    'لا يمكن حذف الدين لأنه يحتوي على دفعات مسجلة.'
                );
            }

            /*
         * Debt has SoftDeletes, but the invoice cannot be deleted
         * while the debt row still exists because invoice_id is restricted.
         *
         * Therefore permanently remove this unused opening-debt record.
         */
            $lockedDebt->forceDelete();

            /*
         * invoice_items are deleted automatically because of
         * cascadeOnDelete().
         */
            $invoice->delete();
        });

        return response()->json([
            'message' => 'تم حذف الدين القديم والفاتورة المرتبطة به بنجاح.',
        ]);
    }
}
