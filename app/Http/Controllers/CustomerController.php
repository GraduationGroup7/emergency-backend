<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerCollection;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function getCustomers(Request $request): CustomerCollection
    {;
        return new CustomerCollection(Customer::query()->paginate(15));
    }
}
