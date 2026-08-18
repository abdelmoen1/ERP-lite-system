<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $storeId = $request->user()->store_id;

        $customers = Customer::query()
            ->where('store_id', $storeId)
            ->withCount('debts')
            ->latest()
            ->paginate(10);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create([
            ...$request->validated(),
            'store_id' => $request->user()->store_id,
        ]);

        return response()->json([
            'message' => 'تم انشاء العميل بنجاح',
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Customer $customer)
    {
        abort_unless(
            $customer->store_id === $request->user()->store_id,
            404
        );

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ) {
        abort_unless(
            $customer->store_id === $request->user()->store_id,
            404
        );

        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Customer $customer)
    {
        abort_unless(
            $customer->store_id === $request->user()->store_id,
            404
        );

        $hasOpenDebts = $customer->debts()
            ->whereIn('status', ['open', 'partially_paid'])
            ->exists();

        if ($hasOpenDebts) {
            return response()->json([
                'message' => 'لا يمكن حذف عميل لديه ديون مفتوحة أو مدفوعة جزئيًا',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'message' => 'تم حذف العميل بنجاح'
        ]);
    }
}
