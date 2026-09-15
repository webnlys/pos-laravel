<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct(private CustomerRepository $customers) {}

    public function index(Request $request)
    {
        return CustomerResource::collection($this->customers->paginate($request, 'name', 'asc'));
    }

    public function store(CustomerRequest $request)
    {
        $customer = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $password = $data['password'];
            unset($data['password']);

            $customer = Customer::query()->create($data);

            User::query()->create([
                'name' => $customer->name,
                'email' => $customer->email,
                'password' => $password,
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);

            return $customer;
        });

        return new CustomerResource($customer);
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);

        $customer->update($data);

        if ($customer->user) {
            $payload = [
                'name' => $customer->name,
                'email' => $customer->email,
            ];
            if ($password) {
                $payload['password'] = $password;
            }
            $customer->user->update($payload);
        }

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->user?->delete();
        $customer->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
