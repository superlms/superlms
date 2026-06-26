<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->whenNotNull($this->email_verified_at),
            'mobile' => $this->whenNotNull($this->mobile),
            'role' => $this->role,
            'profile' => $this->whenNotNull($this->profile),
            'is_active' => $this->is_active,
        ];
    }
}
