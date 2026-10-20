<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\RulesAndRegulation;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * What a school's website reads live from the school's own records, so the
 * Online Admission pages never need typing twice: its classes with their
 * sections and subjects (Standard), its fee structure (Fee → Fee Structure)
 * and its rules (More → Rules & Regulations). Each is read once, when a page
 * asks for it; a school with none of it set up gets an empty list, and the
 * page shows what Website Data holds instead.
 */
class SchoolSiteData
{
    private array $memo = [];

    public function __construct(private ?int $orgId)
    {
    }

    /**
     * The active classes in the Standard page's order, each with its active
     * sections and the subjects taught in it (its own and its sections'):
     * [{name, sections: [name], subjects: [name]}].
     */
    public function classes(): array
    {
        return $this->memo['classes'] ??= $this->read(function () {
            $standards = Standard::where('organization_id', $this->orgId)
                ->where('is_active', true)->inClassOrder()->get(['id', 'name']);
            if ($standards->isEmpty()) {
                return [];
            }
            $ids = $standards->pluck('id')->all();

            $sections = Section::where('organization_id', $this->orgId)
                ->whereIn('standard_id', $ids)->where('is_active', true)
                ->orderBy('id')->get(['id', 'standard_id', 'name'])
                ->groupBy('standard_id');

            // A class's subjects are its own links plus its sections' (as Subject::scopeTaughtIn).
            $own = DB::table('standard_subjects')
                ->join('subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->whereIn('standard_subjects.standard_id', $ids)
                ->where('subjects.is_active', true)
                ->select('standard_subjects.standard_id', 'subjects.name');
            $subjects = DB::table('section_subjects')
                ->join('subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->join('sections', 'sections.id', '=', 'section_subjects.section_id')
                ->whereIn('section_subjects.standard_id', $ids)
                ->whereColumn('sections.standard_id', 'section_subjects.standard_id')
                ->where('subjects.is_active', true)
                ->select('section_subjects.standard_id', 'subjects.name')
                ->union($own)
                ->get()
                ->groupBy('standard_id');

            return $standards->map(fn ($s) => [
                'name'     => trim((string) $s->name),
                'sections' => $this->names($sections->get($s->id)),
                'subjects' => $this->names($subjects->get($s->id), sort: true),
            ])->all();
        });
    }

    /** The active classes' names, for the admission form's Class list. */
    public function classNames(): array
    {
        return array_values(array_filter(array_column($this->classes(), 'name')));
    }

    /**
     * The academic fee particulars, a group a class (or one section of it),
     * in the classes' order — the latest academic year a group has, as Fee
     * Structure lists them: [{class, section, year, rows: [{name, amount}], total}].
     */
    public function fees(): array
    {
        return $this->memo['fees'] ??= $this->read(function () {
            $rows = FeeStructure::with(['standard:id,name,order,is_active', 'section:id,name'])
                ->where('organization_id', $this->orgId)
                ->where('fee_type', 'academic')
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->filter(fn ($r) => $r->standard && $r->standard->is_active);

            return $rows
                ->groupBy(fn ($r) => $r->standard_id . '-' . ($r->section_id ?? 0))
                ->map(function ($g) {
                    $year = (string) $g->max('academic_year');
                    $g    = $g->filter(fn ($r) => (string) $r->academic_year === $year)->values();
                    $first = $g->first();

                    return [
                        'class'   => trim((string) $first->standard->name),
                        'section' => $first->section ? trim((string) $first->section->name) : null,
                        'year'    => $year,
                        'rows'    => $g->map(fn ($r) => ['name' => trim((string) $r->fee_name), 'amount' => (float) $r->amount])->all(),
                        'total'   => (float) $g->sum('amount'),
                        'sort'    => [(int) $first->standard->order, (int) $first->standard_id, (int) ($first->section_id ?? 0)],
                    ];
                })
                ->sort(fn ($a, $b) => $a['sort'] <=> $b['sort'])
                ->map(fn ($g) => array_diff_key($g, ['sort' => 1]))
                ->values()
                ->all();
        });
    }

    /**
     * The school's Rules & Regulations as saved: its rules (a heading and the
     * text), the additional information (a key and a value) and the PDFs.
     * Empty lists while the school has saved none.
     */
    public function rules(): array
    {
        return $this->memo['rules'] ??= $this->read(function () {
            $content = RulesAndRegulation::where('organization_id', $this->orgId)->value('content');
            $content = is_array($content) ? $content : (json_decode((string) $content, true) ?: []);
            $str     = fn ($v) => trim(is_scalar($v) ? (string) $v : '');

            return [
                'sections' => array_values(array_filter(array_map(fn ($s) => ['head' => $str($s['head'] ?? ''), 'desc' => $str($s['desc'] ?? '')],
                    (array) ($content['sections'] ?? [])), fn ($s) => $s['head'] . $s['desc'] !== '')),
                'info'     => array_values(array_filter(array_map(fn ($i) => ['key' => $str($i['key'] ?? ''), 'value' => $str($i['value'] ?? '')],
                    (array) ($content['additional_info'] ?? [])), fn ($i) => $i['value'] !== '')),
                'files'    => array_values(array_filter(array_map(fn ($f) => ['title' => $str($f['title'] ?? '') ?: 'Document', 'url' => $str($f['file_path'] ?? '')],
                    (array) ($content['files'] ?? [])), fn ($f) => $f['url'] !== '')),
            ];
        }, ['sections' => [], 'info' => [], 'files' => []]);
    }

    /** Whether the school has saved any rules. */
    public function hasRules(): bool
    {
        $r = $this->rules();

        return (bool) ($r['sections'] || $r['info'] || $r['files']);
    }

    /** An amount as the site shows it: ₹12,500 (paise only when there are some). */
    public static function money(float $amount): string
    {
        return '₹' . number_format($amount, fmod($amount, 1.0) ? 2 : 0);
    }

    /** Each name once, in order (or A to Z). */
    private function names($rows, bool $sort = false): array
    {
        $names = array_values(array_unique(array_filter(array_map(fn ($r) => trim((string) $r->name), $rows ? $rows->all() : []))));
        if ($sort) {
            natcasesort($names);
        }

        return array_values($names);
    }

    /**
     * A read that never takes the website down: a school's site still shows
     * with what Website Data holds when its records can't be read.
     */
    private function read(callable $query, array $empty = []): array
    {
        if (! $this->orgId) {
            return $empty;
        }
        try {
            return $query();
        } catch (QueryException $e) {
            report($e);

            return $empty;
        }
    }
}
