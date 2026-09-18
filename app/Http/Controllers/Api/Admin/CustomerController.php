<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerRepository $customers) {}

    public function index(Request $request)
    {
        return CustomerResource::collection($this->customers->paginate($request, 'name', 'asc'));
    }

    public function store(CustomerRequest $request)
    {
        $customer = Customer::query()->create($request->validated());

        return new CustomerResource($customer);
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->user?->delete();
        $customer->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
