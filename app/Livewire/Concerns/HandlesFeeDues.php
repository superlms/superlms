<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeStructure as FeeStructureModel;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Collection;

/**
 * Fee Structure's Add Dues — what each student still owed from last year.
 * Pick a class (and a section, if you want) and its students are listed, each
 * with a box for the amount. What is entered becomes that student's own fee
 * particular, "Last Year Dues", so their fee is the class's structure plus it;
 * emptying a box takes the student's dues away again.
 *
 * Used by HandlesFeeStructures; the button is on the admin's pages (Fee
 * Structure, and the Fee page's header when it is embedded there) and the
 * markup is in `livewire.partials.fee-structure-panel`.
 */
trait HandlesFeeDues
{
    public bool $duesPanelOpen = false;
    public $duesStandardId = '';
    public $duesSectionId  = '';
    /** ['s{student id}' => amount as typed] — the prefix keeps it a keyed map. */
    public array $duesAmounts = [];

    public function openDuesPanel(): void
    {
        $this->reset(['duesStandardId', 'duesSectionId', 'duesAmounts']);
        $this->resetValidation();
        $this->duesPanelOpen = true;
    }

    /** The Fee page's own header carries the button when this component is embedded there. */
    #[\Livewire\Attributes\On('fee-dues-add')]
    public function openDuesPanelFromHost(): void
    {
        $this->structureTab = 'academic';
        $this->openDuesPanel();
    }

    public function closeDuesPanel(): void
    {
        $this->duesPanelOpen = false;
        $this->reset(['duesStandardId', 'duesSectionId', 'duesAmounts']);
        $this->resetValidation();
    }

    /** A class is chosen: its students come up, each with the dues they have now. */
    public function updatedDuesStandardId(): void
    {
        $this->duesSectionId = '';
        $this->duesAmounts   = [];
        $this->resetValidation();

        if (!$this->duesStandardId) {
            return;
        }

        $ids = $this->duesStudentsQuery()->pluck('id')->all();
        foreach ($this->currentDues($ids) as $studentId => $amount) {
            $this->duesAmounts['s' . $studentId] = $this->plainAmount($amount);
        }
    }

    /** The section only narrows the class's list — what was typed stays. */
    public function updatedDuesSectionId(): void
    {
        $this->resetValidation();
    }

    /** The chosen class's sections. */
    public function getDuesSectionsProperty(): Collection
    {
        return $this->duesStandardId
            ? Section::where('organization_id', $this->orgId())
                ->where('standard_id', $this->duesStandardId)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();
    }

    /** The students listed: the class, or the one section of it. */
    public function getDuesStudentsProperty(): Collection
    {
        return $this->duesStandardId
            ? $this->duesStudentsQuery()->with('user:id,name')->get()
            : collect();
    }

    /** What the boxes on screen add up to. */
    public function getDuesTotalProperty(): float
    {
        return round($this->duesStudents->sum(
            fn ($s) => (float) ($this->duesAmounts['s' . $s->id] ?? 0)
        ), 2);
    }

    public function saveDues(): void
    {
        if (!$this->duesStandardId) {
            $this->addError('duesStandardId', 'Select a class.');
            return;
        }

        // The whole class, not only the section on screen: what was typed for
        // another section before the list was narrowed is saved too.
        $students = $this->duesStudentsQuery(wholeClass: true)->get();

        $rules = [];
        foreach ($students as $s) {
            $rules['duesAmounts.s' . $s->id] = 'nullable|numeric|min:0|max:99999999';
        }
        $this->validate($rules, [
            'duesAmounts.*.numeric' => 'Enter an amount.',
            'duesAmounts.*.min'     => 'Enter an amount.',
            'duesAmounts.*.max'     => 'Too large.',
        ]);

        $orgId = $this->orgId();
        $year  = $this->currentStructureYear();
        $rows  = $this->duesRows($students->pluck('id')->all())->keyBy('student_detail_id');

        $saved = $removed = 0;
        foreach ($students as $s) {
            $typed  = trim((string) ($this->duesAmounts['s' . $s->id] ?? ''));
            $amount = $typed === '' ? 0.0 : round((float) $typed, 2);
            $row    = $rows->get($s->id);

            if ($amount > 0) {
                $values = [
                    'standard_id'   => $s->standard_id,
                    'section_id'    => $s->section_id,
                    'amount'        => $amount,
                    'academic_year' => $year,
                    'is_active'     => true,
                ];
                if ($row) {
                    $row->update($values);
                } else {
                    FeeStructureModel::create($values + [
                        'organization_id'   => $orgId,
                        'student_detail_id' => $s->id,
                        'fee_name'          => FeeStructureModel::DUES_NAME,
                        'fee_type'          => 'academic',
                    ]);
                }
                $saved++;
            } elseif ($row) {
                // An emptied box: the student owes nothing from last year.
                $row->delete();
                $removed++;
            }
        }

        $this->notification()->success(
            'Dues saved',
            $saved . ' student' . ($saved === 1 ? '' : 's') . ' with Last Year Dues'
                . ($removed ? ', ' . $removed . ' cleared' : '') . '.'
        );
        $this->closeDuesPanel();
    }

    private function duesStudentsQuery(bool $wholeClass = false)
    {
        return StudentDetail::where('organization_id', $this->orgId())
            ->where('standard_id', $this->duesStandardId)
            ->when($this->duesSectionId && !$wholeClass, fn ($q) => $q->where('section_id', $this->duesSectionId))
            ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')
            ->orderBy('full_name');
    }

    /** The students' Last Year Dues rows as they are saved now. */
    private function duesRows(array $studentIds): Collection
    {
        if (!$studentIds || !FeeStructureModel::hasStudentRows()) {
            return collect();
        }

        return FeeStructureModel::withoutGlobalScope('class_wide')
            ->where('organization_id', $this->orgId())
            ->whereIn('student_detail_id', $studentIds)
            ->where('fee_name', FeeStructureModel::DUES_NAME)
            ->where('fee_type', 'academic')
            ->get();
    }

    /** [student id => their Last Year Dues]. */
    private function currentDues(array $studentIds): array
    {
        return $this->duesRows($studentIds)
            ->mapWithKeys(fn ($r) => [$r->student_detail_id => (float) $r->amount])
            ->all();
    }

    /** 1500.00 → "1500", 1500.50 → "1500.5" — as someone would type it. */
    private function plainAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }
}
