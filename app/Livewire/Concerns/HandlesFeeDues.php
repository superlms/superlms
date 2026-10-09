<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeStructure as FeeStructureModel;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Collection;
use App\Support\NameOrder;

/**
 * Fee Structure's Add Dues — what each student still owed from last year.
 * Pick a class (and a section, if you want) and its students are listed, each
 * with a box for the amount. What is entered becomes that student's own fee
 * particular, "Last Year Dues", so their fee is the class's structure plus it;
 * emptying a box takes the student's dues away again.
 *
 * The + beside a box adds earlier years' fees for that student, each with its
 * own name ("2023-24 Fee") and amount — saved as more of the student's own
 * fee particulars, beside Last Year Dues; × takes one away.
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
    /** ['s{student id}' => [['id' => saved row id|null, 'name' => '', 'amount' => ''], …]] — earlier years' fees. */
    public array $duesExtras = [];

    public function openDuesPanel(): void
    {
        $this->reset(['duesStandardId', 'duesSectionId', 'duesAmounts', 'duesExtras']);
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
        $this->reset(['duesStandardId', 'duesSectionId', 'duesAmounts', 'duesExtras']);
        $this->resetValidation();
    }

    /** A class is chosen: its students come up, each with the dues they have now. */
    public function updatedDuesStandardId(): void
    {
        $this->duesSectionId = '';
        $this->duesAmounts   = [];
        $this->duesExtras    = [];
        $this->resetValidation();

        if (!$this->duesStandardId) {
            return;
        }

        $ids = $this->duesStudentsQuery()->pluck('id')->all();
        foreach ($this->currentDues($ids) as $studentId => $amount) {
            $this->duesAmounts['s' . $studentId] = $this->plainAmount($amount);
        }
        // …and the earlier years' fees already saved for them.
        foreach ($this->extraDuesRows($ids) as $row) {
            $this->duesExtras['s' . $row->student_detail_id][] = [
                'id'     => (int) $row->id,
                'name'   => (string) $row->fee_name,
                'amount' => $this->plainAmount((float) $row->amount),
            ];
        }
    }

    /** The + beside a student's box: one more earlier year's fee for them. */
    public function addDuesExtra(int $studentId): void
    {
        $this->duesExtras['s' . $studentId][] = ['id' => null, 'name' => '', 'amount' => ''];
    }

    /** × on one of those rows: it goes (a saved one is deleted on Save). */
    public function removeDuesExtra(int $studentId, int $index): void
    {
        $key = 's' . $studentId;
        if (isset($this->duesExtras[$key][$index])) {
            unset($this->duesExtras[$key][$index]);
            $this->duesExtras[$key] = array_values($this->duesExtras[$key]);
        }
        $this->resetValidation();
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
                + collect($this->duesExtras['s' . $s->id] ?? [])->sum(fn ($e) => (float) ($e['amount'] ?? 0))
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
            // An earlier year's fee needs both its name and its amount (a row
            // left wholly empty is simply skipped).
            foreach ($this->duesExtras['s' . $s->id] ?? [] as $i => $e) {
                $base = 'duesExtras.s' . $s->id . '.' . $i;
                $rules[$base . '.name']   = 'nullable|string|max:255|required_with:' . $base . '.amount';
                $rules[$base . '.amount'] = 'nullable|numeric|min:0|max:99999999|required_with:' . $base . '.name';
            }
        }
        $this->validate($rules, [
            'duesAmounts.*.numeric'          => 'Enter an amount.',
            'duesAmounts.*.min'              => 'Enter an amount.',
            'duesAmounts.*.max'              => 'Too large.',
            'duesExtras.*.*.name.required_with'   => 'Enter the fee name.',
            'duesExtras.*.*.name.max'             => 'Too long.',
            'duesExtras.*.*.amount.required_with' => 'Enter an amount.',
            'duesExtras.*.*.amount.numeric'       => 'Enter an amount.',
            'duesExtras.*.*.amount.min'           => 'Enter an amount.',
            'duesExtras.*.*.amount.max'           => 'Too large.',
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

        $extraSaved = $this->saveExtraDues($students, $year);

        $this->notification()->success(
            'Dues saved',
            $saved . ' student' . ($saved === 1 ? '' : 's') . ' with Last Year Dues'
                . ($removed ? ', ' . $removed . ' cleared' : '')
                . ($extraSaved ? ', ' . $extraSaved . ' earlier year fee' . ($extraSaved === 1 ? '' : 's') : '') . '.'
        );
        $this->closeDuesPanel();
    }

    private function duesStudentsQuery(bool $wholeClass = false)
    {
        return StudentDetail::where('organization_id', $this->orgId())
            ->where('standard_id', $this->duesStandardId)
            ->when($this->duesSectionId && !$wholeClass, fn ($q) => $q->where('section_id', $this->duesSectionId))
            ->tap(fn ($q) => NameOrder::students($q));
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

    /** The students' earlier years' fees: their own academic rows other than Last Year Dues. */
    private function extraDuesRows(array $studentIds): Collection
    {
        if (!$studentIds || !FeeStructureModel::hasStudentRows()) {
            return collect();
        }

        return FeeStructureModel::withoutGlobalScope('class_wide')
            ->where('organization_id', $this->orgId())
            ->whereIn('student_detail_id', $studentIds)
            ->where('fee_type', 'academic')
            ->where('fee_name', '!=', FeeStructureModel::DUES_NAME)
            ->orderBy('id')
            ->get();
    }

    /**
     * Earlier years' fees as the panel holds them: a filled row is saved (a
     * saved one updated in place), a saved row no longer there is deleted.
     * Returns how many rows are kept.
     */
    private function saveExtraDues(Collection $students, string $year): int
    {
        if (!FeeStructureModel::hasStudentRows()) {
            return 0;
        }

        $orgId = $this->orgId();
        $saved = $this->extraDuesRows($students->pluck('id')->all())->groupBy('student_detail_id');
        $kept  = 0;

        foreach ($students as $s) {
            $rows   = $saved->get($s->id, collect())->keyBy('id');
            $stayed = [];

            foreach ($this->duesExtras['s' . $s->id] ?? [] as $e) {
                $name   = trim((string) ($e['name'] ?? ''));
                $typed  = trim((string) ($e['amount'] ?? ''));
                $amount = $typed === '' ? 0.0 : round((float) $typed, 2);
                if ($name === '' || $amount <= 0) {
                    continue;                          // an empty row, or nothing owed
                }

                $values = [
                    'standard_id'   => $s->standard_id,
                    'section_id'    => $s->section_id,
                    'fee_name'      => $name,
                    'amount'        => $amount,
                    'academic_year' => $year,
                    'is_active'     => true,
                ];
                $row = !empty($e['id']) ? $rows->get((int) $e['id']) : null;
                if ($row) {
                    $row->update($values);
                    $stayed[] = (int) $row->id;
                } else {
                    $stayed[] = (int) FeeStructureModel::create($values + [
                        'organization_id'   => $orgId,
                        'student_detail_id' => $s->id,
                        'fee_type'          => 'academic',
                    ])->id;
                }
                $kept++;
            }

            // A saved row taken off the panel (×, or emptied) goes.
            foreach ($rows as $id => $row) {
                if (!in_array((int) $id, $stayed, true)) {
                    $row->delete();
                }
            }
        }

        return $kept;
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
