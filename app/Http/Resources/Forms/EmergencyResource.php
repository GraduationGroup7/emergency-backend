<?php

namespace App\Http\Resources\Forms;

use App\Models\Emergency;
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
        $statuses = [Emergency::STATUS_PENDING, Emergency::STATUS_COMPLETED, Emergency::STATUS_ABANDONED];
        $statuses = array_map(function ($status) {
            return [
                'id' => $status,
                'name' => $status,
            ];
        }, $statuses);
        return [
            [
                'title' => 'ID',
                'field' => 'id',
                'value' => $this->id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'Reporting User ID',
                'field' => 'reporting_user_id',
                'value' => $this->reporting_user_id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'Approving Agent ID',
                'field' => 'approving_authority_id',
                'value' => $this->approving_authority_id,
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
                'type' => 'select',
                'options' => $statuses,
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
