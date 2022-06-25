<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerCollection;
use App\Http\Resources\Forms\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Util\Exception;

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

    public function deleteCustomer(Request $request, $id): JsonResponse
    {
        $customer = Customer::find($id);
        if(!$customer) {
            return res('Customer not found', 404);
        }

        DB::beginTransaction();
        try {
            $user = User::query()->find($customer->user_id);
            $user->delete();
            $customer->delete();

            DB::commit();
            return res('Customer deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            return res('Customer could not be deleted', 500);
        }
    }

    public function deleteCustomerById($id) {
        $customer = Customer::find($id);
        if(!$customer) throw new Exception('Customer not found');

        $user = User::find($customer->user_id);
        $customer->delete();
        $user->delete();
    }

    public function bulkDeleteCustomers(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach($request->ids as $customerId) {
                $this->deleteCustomerById($customerId);
            }
            DB::commit();
            return res('Customers deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return res('Customers could not be deleted', 400);
        }
    }
}
