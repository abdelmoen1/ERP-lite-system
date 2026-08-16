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
        $customers = Customer::select([
            'id',
            'name',
            'phone',
            'address',
            'notes',
        ])->paginate(10);

        return CustomerResource::collection($customers);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        // we have two functions find(), findOrFail()
        // if he search for a 999 id find() return null
        // if he search for it an uses findOrFail() it return
        //{
        // "message": "No query results for model [Customer]"
        // }

        // laravel uses method called Route Model Binding
        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $hasOpenDebts = $customer->debts()->whereIn('status', ['open', 'partially_paid'])->exists();

        if ($hasOpenDebts) {
            return response()->json([
                'message' => 'لا يمكن حذف عميل لديه ديون مفتوحة أو مدفوعة جزئيًا',
            ], 422);
        }

        $customer->delete();

        return response()->json(['message' => 'تم حذف العميل بنجاح']);
    }
}
