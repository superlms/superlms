<?php

namespace App\Livewire\Concerns;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\NameOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * A form's Class → Section → Student pickers (livewire.admin._partials.
 * student-picker), as Complaints and Gatepass use them: only the school's own
 * classes, their sections, and that section's students A to Z. Changing the
 * class clears the section and the student; changing the section, the student.
 */
trait PicksStudent
{
    public string $standardId = '';
    public string $sectionId  = '';
    public string $studentId  = '';

    protected function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    public function updatedStandardId(): void
    {
        $this->sectionId = '';
        $this->studentId = '';
    }

    public function updatedSectionId(): void
    {
        $this->studentId = '';
    }

    protected function pickerStandards(): Collection
    {
        return Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)
            ->inClassOrder()
            ->get(['id', 'name']);
    }

    protected function pickerSections(): Collection
    {
        if ($this->standardId === '') {
            return collect();
        }

        return Section::where('standard_id', (int) $this->standardId)
            ->whereIn('standard_id', Standard::where('organization_id', $this->orgId())->select('id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function pickerStudents(): Collection
    {
        if ($this->standardId === '' || $this->sectionId === '') {
            return collect();
        }

        return NameOrder::students(
            StudentDetail::where('organization_id', $this->orgId())
                ->where('standard_id', (int) $this->standardId)
                ->where('section_id', (int) $this->sectionId)
        )->get(['id', 'full_name', 'admission_no', 'roll_no']);
    }

    /** The picked student — only when they are this school's and in the picked class and section. */
    protected function pickedStudent(): ?StudentDetail
    {
        if ($this->studentId === '' || $this->standardId === '' || $this->sectionId === '') {
            return null;
        }

        return StudentDetail::where('organization_id', $this->orgId())
            ->where('standard_id', (int) $this->standardId)
            ->where('section_id', (int) $this->sectionId)
            ->find((int) $this->studentId);
    }

    /** Puts the pickers on a saved row's student. */
    protected function pickStudent(?int $standardId, ?int $sectionId, ?int $studentId): void
    {
        $this->standardId = $standardId ? (string) $standardId : '';
        $this->sectionId  = $sectionId ? (string) $sectionId : '';
        $this->studentId  = $studentId ? (string) $studentId : '';
    }

    /** The pickers' rules: a class, a section and a student must be chosen. */
    protected function pickerRules(): array
    {
        return [
            'standardId' => 'required',
            'sectionId'  => 'required',
            'studentId'  => 'required',
        ];
    }

    protected function pickerMessages(): array
    {
        return [
            'standardId.required' => 'Please select a class.',
            'sectionId.required'  => 'Please select a section.',
            'studentId.required'  => 'Please select a student.',
        ];
    }
}
