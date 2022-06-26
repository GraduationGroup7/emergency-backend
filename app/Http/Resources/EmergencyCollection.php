<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmergencyCollection extends ResourceCollection
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
            'columns' => [
                [
                    'headerName' => 'Emergency ID',
                    'field' => 'id',
                ],
                [
                    'headerName' => 'Reported By User ID',
                    'field' => 'reporting_user_id'
                ], [
                    'headerName' => 'Approved By Authority ID',
                    'field' => 'approving_authority_id'
                ], [
                    'headerName' => 'Description',
                    'field' => 'description'
                ],
                [
                    'headerName' => 'Emergency Type ID',
                    'field' => 'emergency_type_id'
                ],
                [
                    'headerName' => 'Reported At',
                    'field' => 'created_at'
                ],
            ],
        ];
    }
}
