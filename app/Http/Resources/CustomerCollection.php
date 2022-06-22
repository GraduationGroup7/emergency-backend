<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'date_interval' => true,
            'columns' => [
                [
                    'headerName' => 'Customer ID',
                    'field' => 'id',
                ],
                [
                    'headerName' => 'User ID',
                    'field' => 'user_id'
                ], [
                    'headerName' => 'First Name',
                    'field' => 'first_name'
                ], [
                    'headerName' => 'Last Name',
                    'field' => 'last_name'
                ],
                [
                    'headerName' => 'Date of Birth',
                    'field' => 'dob'
                ],
                [
                    'headerName' => 'Verified',
                    'field' => 'verified'
                ],
            ],
        ];
    }
}
