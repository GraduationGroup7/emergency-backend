<?php

namespace App\Http\Resources\Forms;

use App\Models\AuthorityType;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = User::find($this->user_id);
        $type = $this->authority_type_id ?
            AuthorityType::query()->find($this->authority_type_id)->name : AuthorityType::query()->first()->id;
        $types = AuthorityType::query()->select('id', 'name')->get()->toArray();
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
                'title' => 'Email',
                'field' => 'email',
                'value' => $user?->email,
                'type' => 'text',
                'disabled' => !!$user?->email,
            ],
            [
                'title' => 'Password',
                'field' => 'password',
                'value' => '',
                'type' => 'password',
                'disabled' => !!$user?->password,
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
                'value' => $user?->phone_number,
                'type' => 'text',
                'disabled' => false,
            ],
            [
                'title' => 'Type',
                'field' => 'type',
                'value' => $type,
                'type' => 'select',
                'options' => $types,
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
