<?php

namespace App\Http\Resources;

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
            'id' => $this->id,
            'reporting_user_id' => $this->reporting_user_id,
            'approving_authority_id' => $this->approving_authority_id,
            'completed' => $this->completed,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'emergency_type_id' => $this->emergency_type_id,
            'emergency_type' => $this->emergency_type,
            'description' => $this->description,
            'country' => $this->country,
            'city' => $this->city,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'status' => $this->status,
        ];
    }
}
