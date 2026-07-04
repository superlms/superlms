<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Services\Lists\ListReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Admin → Lists.
 *
 * A create-list wizard: pick a list type (students, teachers, fee, transport,
 * exams, performance, attendance, id/admit cards …), choose the filters and
 * the columns you want, add any blank columns, then generate a PDF table.
 */
class Lists extends Component
{
    public $organization = null;

    public bool $showPanel = false;
    public int $step = 1;             // 1 = choose type, 2 = configure

    public string $type = '';
    public string $title = '';
    public string $orientation = 'portrait';

    // Filters (only the relevant ones are shown per type)
    public $standardId = '';
    public $sectionId  = '';
    public $examId     = '';
    public string $month = '';

    /** @var array<int,string> selected column keys */
    public array $selectedColumns = [];
    public $blankColumns = 0;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? Auth::user()?->organization_id;
        $this->month = now()->format('Y-m');
    }

    public function openPanel(): void
    {
        $this->reset(['step', 'type', 'title', 'standardId', 'sectionId', 'examId', 'selectedColumns', 'blankColumns']);
        $this->step = 1;
        $this->orientation = 'portrait';
        $this->month = now()->format('Y-m');
        $this->resetErrorBag();
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
    }

    /** Landing-card shortcut: open the panel straight on a type's config step. */
    public function startWith(string $type): void
    {
        $this->openPanel();
        $this->selectType($type);
    }

    /** Choose a list type and jump to the configuration step. */
    public function selectType(string $type): void
    {
        $defs = ListReportService::definitions();
        if (!isset($defs[$type])) {
            return;
        }
        $this->type = $type;
        $this->title = $defs[$type]['label'] . ' List';
        // Pre-select every column; the user can trim it down.
        $this->selectedColumns = array_keys($defs[$type]['columns']);
        $this->blankColumns = 0;
        $this->standardId = $this->sectionId = $this->examId = '';
        $this->orientation = count($defs[$type]['columns']) > 7 ? 'landscape' : 'portrait';
        $this->resetErrorBag();
        $this->step = 2;
    }

    public function backToTypes(): void
    {
        $this->step = 1;
        $this->resetErrorBag();
    }

    public function updatedStandardId(): void
    {
        $this->sectionId = '';
    }

    public function toggleAllColumns(bool $all): void
    {
        $defs = ListReportService::definitions();
        $this->selectedColumns = $all ? array_keys($defs[$this->type]['columns'] ?? []) : [];
    }

    public function generate()
    {
        $defs = ListReportService::definitions();
        $def  = $defs[$this->type] ?? null;
        if (!$def) {
            return;
        }

        // Validate required filters for this type.
        $rules = [];
        $attrs = [];
        foreach ($def['filters'] as $name => $rule) {
            if ($rule !== 'required') {
                continue;
            }
            $prop = match ($name) {
                'standard' => 'standardId',
                'section'  => 'sectionId',
                'exam'     => 'examId',
                'month'    => 'month',
                default    => $name,
            };
            $rules[$prop] = 'required';
            $attrs[$prop] = ucfirst($name);
        }
        if ($rules) {
            $this->validate($rules, [], $attrs);
        }

        if (empty($this->selectedColumns) && $this->blankColumns < 1) {
            $this->addError('selectedColumns', 'Pick at least one column (or add blank columns).');
            return;
        }

        $query = array_filter([
            'type'        => $this->type,
            'title'       => $this->title,
            'orientation' => $this->orientation,
            'columns'     => implode(',', $this->selectedColumns),
            'blanks'      => $this->blankColumns,
            'standard_id' => $this->standardId ?: null,
            'section_id'  => $this->sectionId ?: null,
            'exam_id'     => $this->examId ?: null,
            'month'       => in_array('month', array_keys($def['filters'])) ? $this->month : null,
        ], fn ($v) => $v !== null && $v !== '');

        $url = route('admin.lists.pdf', ['organization' => $this->organization]) . '?' . http_build_query($query);

        $this->showPanel = false;
        $this->dispatch('open-list-pdf', url: $url);
    }

    public function render()
    {
        $orgId = Auth::user()?->organization_id;

        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('id')->get(['id', 'name']);

        $sections = $this->standardId
            ? Section::where('standard_id', $this->standardId)->where('is_active', true)
                ->orderBy('id')->get(['id', 'name'])
            : collect();

        $exams = Exam::where('organization_id', $orgId)->orderByDesc('start_date')
            ->get(['id', 'exam_name', 'academic_year']);

        return view('livewire.admin.lists', [
            'definitions' => ListReportService::definitions(),
            'standards'   => $standards,
            'sections'    => $sections,
            'exams'       => $exams,
        ]);
    }
}
