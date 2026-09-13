<?php

namespace App\Http\Resources;

use App\Models\Teacher\AssignTeacherStandard;
use Carbon\Carbon;
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
            'class_teacher'     => $this->classTeacherName(),
        ];
    }

    protected function formatTransportInfo(): array
    {
        // The route the student rides, when one is assigned and still running.
        $route = $this->relationLoaded('transportations')
            ? $this->transportations->where('is_active', true)->first()
            : null;

        return [
            'transportation_required' => (bool) ($this->transportation_required ?? false),
            'active_transport'        => $route ? [
                'id'              => $route->id,
                'route_name'      => $route->route_name ?? null,
                'vehicle_type'    => $route->vehicle_type ?? null,
                'vehicle_no'      => $route->driver->vehicle_no ?? null,
                'pickup_location' => $route->pickup_location ?? null,
                'pickup_time'     => $this->clock($route->pickup_time),
                'drop_location'   => $route->drop_location ?? null,
                'drop_time'       => $this->clock($route->drop_time),
                'driver_name'     => $route->driver?->user?->name,
                'driver_phone'    => $route->driver->phone ?? null,
                'is_active'       => (bool) $route->is_active,
                // Earlier names for the same things.
                'route'           => $route->route_name ?? null,
                'pickup_point'    => $route->pickup_location ?? null,
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

    // The class teacher of the student's class: whoever is set for the section,
    // or else whoever is set for the whole class (no section).
    protected function classTeacherName(): ?string
    {
        if (!$this->standard_id) return null;

        $assigned = AssignTeacherStandard::with('teacher.user:id,name')
            ->where('organization_id', $this->organization_id)
            ->where('standard_id', $this->standard_id)
            ->where(fn($q) => $q->where('section_id', $this->section_id ?: -1)
                ->orWhereNull('section_id')
                ->orWhere('section_id', 0))
            ->get();

        $forSection = $assigned->filter(fn($a) => $a->section_id && $a->section_id == $this->section_id);

        $names = ($forSection->isNotEmpty() ? $forSection : $assigned)
            ->map(fn($a) => $a->teacher?->user?->name)
            ->filter()
            ->unique();

        return $names->isNotEmpty() ? $names->implode(', ') : null;
    }

    // "07:30 AM"
    private function clock($time): ?string
    {
        if (!$time) return null;

        try {
            return Carbon::parse($time)->format('h:i A');
        } catch (\Throwable) {
            return (string) $time;
        }
    }
}
