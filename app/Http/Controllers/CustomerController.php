<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerCollection;
use App\Http\Resources\Forms\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function getCustomers(Request $request): CustomerCollection
    {
        return new CustomerCollection(kaantable(Customer::query(), $request));
    }

    public function getCustomer($id): JsonResponse
    {
        $customer = Customer::find($id);
        if(!$customer)
        {
            return res('Customer not found', 404);
        }
        return res($customer);
    }

    public function getCustomerForm(Request $request, $id): JsonResponse
    {
        $customer = Customer::query()->find($id);
        if (!$customer) {
            return res('Customer not found', 404);
        }

        return res(new CustomerResource($customer));
    }

    public function getCustomerCreateForm(Request $request): JsonResponse
    {
        $newCustomer = new Customer();
        return res(new CustomerResource($newCustomer));
    }

    public function updateCustomer(Request $request, $id): JsonResponse
    {
        $customer = Customer::find($id);
        if(!$customer)
        {
            return res('Customer not found', 404);
        }
        $customer->update($request->all());
        User::query()->find($customer->user_id)->update([
            'phone_number' => $request->phone_number,
        ]);

        return res($customer);
    }
}
