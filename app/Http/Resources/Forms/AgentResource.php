<?php

namespace App\Http\Resources\Forms;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
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
                'title' => 'User ID',
                'field' => 'user_id',
                'value' => $this->user_id,
                'type' => 'number',
                'disabled' => true,
            ],
            [
                'title' => 'First Name',
                'field' => 'first_name',
                'value' => $this->first_name,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Last Name',
                'field' => 'last_name',
                'value' => $this->last_name,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Phone Number',
                'field' => 'phone_number',
                'value' => User::find($this->user_id)?->phone_number,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Created At',
                'field' => 'created_at',
                'value' => $this->created_at,
                'type' => 'date',
                'disabled' => false,
            ],
            [
                'title' => 'Updated At',
                'field' => 'updated_at',
                'value' => $this->updated_at,
                'type' => 'date',
                'disabled' => false,
            ],
        ];
    }
}
