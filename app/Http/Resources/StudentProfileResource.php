<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'personal_info'      => $this->formatPersonalInfo(),
            'family_info'        => $this->formatFamilyInfo(),
            'address_info'       => $this->formatAddressInfo(),
            'academic_info'      => $this->formatAcademicInfo(),
            'transport_info'     => $this->formatTransportInfo(),
            'organization_info'  => $this->formatOrganizationInfo(),
        ];
    }

    protected function formatPersonalInfo(): array
    {
        return [
            'student_detail_id'   => $this->id ?? null,
            'user_id'             => $this->user_id ?? null,
            'full_name'           => $this->full_name ?? ($this->user->name ?? null),
            'email'               => $this->email ?? ($this->user->email ?? null),
            'mobile_number'       => $this->user->mobile_number ?? null,
            'phone'               => $this->phone ?? null,
            'dob'                 => $this->dob ? $this->dob->format('Y-m-d') : null,
            'gender'              => $this->gender ?? null,
            'religion'            => $this->religion ?? null,
            'aadhar_no'           => $this->aadhar_no ?? null,
            'appar_id'            => $this->appar_id ?? null,
            'registration_number' => $this->registration_number ?? null,
            'image'               => $this->image ?? ($this->user->image ?? null),
            'is_active'           => (bool) ($this->user->is_active ?? false),
            'role'                => $this->user->role ?? null,
        ];
    }

    protected function formatFamilyInfo(): array
    {
        return [
            'father_name' => $this->father_name ?? null,
            'mother_name' => $this->mother_name ?? null,
        ];
    }

    protected function formatAddressInfo(): array
    {
        return [
            'local_address'     => $this->local_address ?? null,
            'permanent_address' => $this->permanent_address ?? null,
            'city'              => $this->city ?? null,
            'state'             => $this->state ?? null,
            'pincode'           => $this->pincode ?? null,
        ];
    }

    protected function formatAcademicInfo(): array
    {
        return [
            'admission_no'      => $this->admission_no ?? null,
            'date_of_admission' => $this->date_of_admission ? $this->date_of_admission->format('Y-m-d') : null,
            'roll_no'           => $this->roll_no ?? null,
            'board'             => $this->board ?? null,
            'standard_id'       => $this->standard_id ?? null,
            'standard_name'     => $this->standard->name ?? null,
            'standard_code'     => $this->standard->code ?? null,
            'section_id'        => $this->section_id ?? null,
            'section_name'      => $this->section->name ?? null,
        ];
    }

    protected function formatTransportInfo(): array
    {
        $activeTransport = $this->whenLoaded('transportations', function () {
            return $this->transportations->where('is_active', true)->first();
        });

        return [
            'transportation_required' => (bool) ($this->transportation_required ?? false),
            'active_transport'        => $activeTransport ? [
                'id'           => $activeTransport->id,
                'vehicle_no'   => $activeTransport->vehicle_no ?? null,
                'route'        => $activeTransport->route ?? null,
                'pickup_point' => $activeTransport->pickup_point ?? null,
                'is_active'    => (bool) $activeTransport->is_active,
            ] : null,
        ];
    }

    protected function formatOrganizationInfo(): array
    {
        return [
            'organization_id' => $this->organization_id ?? null,
            'name'            => $this->organization->name ?? null,
            'code'            => $this->organization->code ?? null,
            'logo_url'        => $this->organization->logo ?? null,
        ];
    }
}
