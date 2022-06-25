<?php

namespace App\Http\Resources\Forms;

use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            [
                'title' => 'ID',
                'field' => 'id',
                'value' => $this->id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'Reporting Customer ID',
                'field' => 'reporting_customer_id',
                'value' => $this->reported_customer_id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'Approving Agent ID',
                'field' => 'approving_authority_id',
                'value' => $this->approving_agent_id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'Latitude',
                'field' => 'latitude',
                'value' => $this->latitude,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Longitude',
                'field' => 'longitude',
                'value' => $this->longitude,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'description',
                'field' => 'description',
                'value' => $this->description,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Emergency Type ID',
                'field' => 'emergency_type_id',
                'value' => $this->emergency_type_id,
                'type' => 'number',
                'disabled' => false,
            ],
            [
                'title' => 'Status',
                'field' => 'status',
                'value' => $this->status,
                'type' => 'text',
                'disabled' => false,
            ],

            [
                'title' => 'Completed',
                'field' => 'completed',
                'value' => $this->completed,
                'type' => 'checkbox',
                'disabled' => false,
            ],

            [
                'title' => 'Is Active',
                'field' => 'is_active',
                'value' => $this->is_active,
                'type' => 'checkbox',
                'disabled' => false,
            ],
        ];
    }
}
