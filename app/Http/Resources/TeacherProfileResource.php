<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'personal_info'      => $this->formatPersonalInfo(),
            'professional_info'  => $this->formatProfessionalInfo(),
            'address_info'       => $this->formatAddressInfo(),
            'assignments'        => $this->getAllAssignmentsFormatted(),
            'organization_info'  => $this->formatOrganizationInfo(),
        ];
    }

    protected function formatPersonalInfo(): array
    {
        return [
            'teacher_detail_id' => $this->id ?? null,
            'user_id'           => $this->user_id ?? null,
            'name'              => $this->user->name ?? null,
            'email'             => $this->user->email ?? null,
            'mobile_number'     => $this->user->mobile_number ?? null,
            'phone'             => $this->phone ?? null,
            'emergency_contact' => $this->emergency_contact ?? null,
            'gender'            => $this->user->gender ?? null,
            'dob'               => $this->user->dob ? \Carbon\Carbon::parse($this->user->dob)->format('Y-m-d') : null,
            'image'             => $this->user->image ?? null,
            'role'              => $this->user->role ?? null,
            'is_active'         => (bool) ($this->user->is_active ?? false),
        ];
    }

    protected function formatProfessionalInfo(): array
    {
        return [
            'employee_id'              => $this->employee_id ?? null,
            'date_of_joining'          => $this->date_of_joining ? \Carbon\Carbon::parse($this->date_of_joining)->format('Y-m-d') : null,
            'qualification'            => $this->qualification ?? null,
            'total_subjects_assigned'  => $this->assignedSubjects->count(),
            'total_classes_assigned'   => $this->assignedClasses->count(),
            'total_sections_assigned'  => $this->teacherSections->count(),
        ];
    }

    protected function formatAddressInfo(): array
    {
        return [
            'address' => $this->address ?? null,
            'city'    => $this->city ?? null,
            'state'   => $this->state ?? null,
            'pincode' => $this->pincode ?? null,
        ];
    }

    protected function getAllAssignmentsFormatted(): array
    {
        $subjects = $this->assignedSubjects->map(function ($subject) {
            return [
                'assignment_type' => 'subject',
                'subject_id'      => $subject->subject_id ?? null,
                'subject_name'    => optional($subject->subject)->name ?? null,
                'subject_code'    => optional($subject->subject)->code ?? null,
                'standard_id'     => $subject->standard_id ?? null,
                'standard_name'   => optional($subject->standard)->name ?? null,
                'section_id'      => $subject->section_id ?? null,
                'section_name'    => optional($subject->section)->name ?? null,
                'source'          => 'teacher_subjects',
            ];
        });

        $classes = $this->assignedClasses->map(function ($class) {
            return [
                'assignment_type' => 'class',
                'standard_id'     => $class->standard_id ?? null,
                'standard_name'   => optional($class->standard)->name ?? null,
                'section_id'      => $class->section_id ?? null,
                'section_name'    => optional($class->section)->name ?? null,
                'source'          => 'assign_teacher_standard',
            ];
        });

        $sections = $this->teacherSections->map(function ($ts) {
            return [
                'assignment_type' => 'section',
                'section_id'      => $ts->section_id ?? null,
                'section_name'    => optional($ts->section)->name ?? null,
                'standard_id'     => optional($ts->section)->standard_id ?? null,
                'standard_name'   => optional(optional($ts->section)->standard)->name ?? null,
                'source'          => 'teacher_sections',
            ];
        })->unique();

        return [
            'subjects' => $subjects->values(),
            'classes'  => $classes->values(),
            'sections' => $sections->values(),
            'summary'  => [
                'total_assignments' => $subjects->count() + $classes->count() + $sections->count(),
                'has_data'          => $subjects->isNotEmpty() || $classes->isNotEmpty() || $sections->isNotEmpty(),
            ],
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
