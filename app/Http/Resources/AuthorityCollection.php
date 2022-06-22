<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AuthorityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'columns' => [
                [
                    'headerName' => 'Authority ID',
                    'field' => 'id',
                ],
                [
                    'headerName' => 'First Name',
                    'field' => 'first_name'
                ], [
                    'headerName' => 'Last Name',
                    'field' => 'last_name'
                ], [
                    'headerName' => 'Created At',
                    'field' => 'created_at'
                ],
            ],
        ];
    }
}
