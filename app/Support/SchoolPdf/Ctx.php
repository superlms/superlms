<?php

namespace App\Support\SchoolPdf;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One school's lookups for the School Data PDFs, each loaded once: class,
 * section, subject and people names by id, and the classes in class order —
 * so every section reads names the same way without a relation per row.
 * Also the small formatters every section shares.
 */
class Ctx
{
    private array $cache = [];

    public function __construct(public readonly int $org)
    {
    }

    // ─── Lookups ──────────────────────────────────────────────────────────────

    /** [standard id => name] in class order. */
    public function classes(): array
    {
        return $this->cache['classes'] ??= \App\Models\Student\Standard::where('organization_id', $this->org)
            ->inClassOrder()->pluck('name', 'id')->all();
    }

    /** [standard id => position in class order]. */
    public function classRank(): array
    {
        return $this->cache['classRank'] ??= array_flip(array_keys($this->classes()));
    }

    /** [section id => name]. */
    public function sections(): array
    {
        return $this->cache['sections'] ??= DB::table('sections')->where('organization_id', $this->org)
            ->orWhereIn('standard_id', array_keys($this->classes()))
            ->pluck('name', 'id')->all();
    }

    /** [subject id => name]. */
    public function subjects(): array
    {
        return $this->cache['subjects'] ??= DB::table('subjects')->where('organization_id', $this->org)->pluck('name', 'id')->all();
    }

    /** [user id => name] for the school's logins. */
    public function users(): array
    {
        return $this->cache['users'] ??= DB::table('users')->where('organization_id', $this->org)->pluck('name', 'id')->all();
    }

    /** [teacher_details id => name]. */
    public function teachers(): array
    {
        return $this->cache['teachers'] ??= DB::table('teacher_details')
            ->leftJoin('users', 'users.id', '=', 'teacher_details.user_id')
            ->where('teacher_details.organization_id', $this->org)
            ->pluck('users.name', 'teacher_details.id')->map(fn ($n) => (string) $n)->all();
    }

    /** [admin_employees id => name]. */
    public function employees(): array
    {
        return $this->cache['employees'] ??= DB::table('admin_employees')->where('organization_id', $this->org)->pluck('name', 'id')->all();
    }

    /**
     * [student_details id => [name, class, section, standard_id, adm, roll, father, phone, user_id]].
     */
    public function students(): array
    {
        if (isset($this->cache['students'])) {
            return $this->cache['students'];
        }

        $out = [];
        foreach (DB::table('student_details')->where('organization_id', $this->org)
            ->get(['id', 'user_id', 'full_name', 'standard_id', 'section_id', 'admission_no', 'roll_no', 'father_name', 'phone']) as $s) {
            $out[$s->id] = [
                'name'        => (string) $s->full_name,
                'class'       => $this->classLabel($s->standard_id, $s->section_id),
                'standard_id' => $s->standard_id,
                'section_id'  => $s->section_id,
                'adm'         => $s->admission_no,
                'roll'        => $s->roll_no,
                'father'      => $s->father_name,
                'phone'       => $s->phone,
                'user_id'     => $s->user_id,
            ];
        }

        return $this->cache['students'] = $out;
    }

    public function student(?int $id): array
    {
        return $this->students()[(int) $id] ?? ['name' => $id ? 'Removed student #' . $id : '—', 'class' => '—', 'standard_id' => null,
            'section_id' => null, 'adm' => null, 'roll' => null, 'father' => null, 'phone' => null, 'user_id' => null];
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    /** "Class 5 - A", "Class 5", or a dash. */
    public function classLabel($standardId, $sectionId = null): string
    {
        $class   = $standardId ? ($this->classes()[$standardId] ?? null) : null;
        $section = $sectionId ? ($this->sections()[$sectionId] ?? null) : null;

        return trim(($class ?? '') . ($section ? ' - ' . $section : '')) ?: '—';
    }

    public function subject($id): string
    {
        return $id ? ($this->subjects()[$id] ?? '—') : '—';
    }

    public function user($id): string
    {
        if ($id === null || $id === '') {
            return '—';
        }
        if (!ctype_digit((string) $id)) {
            return (string) $id;
        }

        return $this->users()[(int) $id] ?? (DB::table('users')->where('id', (int) $id)->value('name') ?: '—');
    }

    /** Sorts rows by class order, then section, then a name. */
    public function byClass(array $rows, callable $standard, callable $section, callable $name): array
    {
        $rank = $this->classRank();
        usort($rows, function ($a, $b) use ($rank, $standard, $section, $name) {
            return [($rank[$standard($a)] ?? PHP_INT_MAX), mb_strtolower((string) ($this->sections()[$section($a)] ?? '')), mb_strtolower(trim((string) $name($a)))]
                <=> [($rank[$standard($b)] ?? PHP_INT_MAX), mb_strtolower((string) ($this->sections()[$section($b)] ?? '')), mb_strtolower(trim((string) $name($b)))];
        });

        return $rows;
    }

    public static function dash($v): string
    {
        if ($v === null || (is_string($v) && trim($v) === '')) {
            return '—';
        }

        return is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v;
    }

    public static function date($v, string $format = 'd M Y'): string
    {
        if (!$v) {
            return '—';
        }
        try {
            return Carbon::parse($v)->format($format);
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    public static function time($v): string
    {
        return $v ? self::date('2000-01-01 ' . substr((string) $v, 0, 8), 'h:i A') : '—';
    }

    public static function money($v): string
    {
        $v = round((float) $v, 2);

        return ($v < 0 ? '-' : '') . '₹' . number_format(abs($v), fmod(abs($v), 1) > 0 ? 2 : 0);
    }

    public static function pct(float $part, float $whole): string
    {
        return $whole > 0 ? round($part / $whole * 100, 1) . '%' : '—';
    }

    /** cash / upi / bank_transfer → Cash / UPI / Bank Transfer. */
    public static function mode($v): string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return '—';
        }

        return strtolower($v) === 'upi' ? 'UPI' : ucwords(str_replace(['_', '-'], ' ', strtolower($v)));
    }

    public static function headline($v): string
    {
        $v = trim((string) $v);

        return $v === '' ? '—' : \Illuminate\Support\Str::headline($v);
    }

    /** JSON (or a plain string) as readable text. */
    public static function text($v): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        if (is_string($v)) {
            $decoded = json_decode($v, true);
            if (is_array($decoded)) {
                $v = $decoded;
            } else {
                return trim(strip_tags(html_entity_decode($v, ENT_QUOTES))) ?: '—';
            }
        }
        if (is_array($v)) {
            $flat = [];
            array_walk_recursive($v, function ($item, $key) use (&$flat) {
                if ($item !== null && $item !== '') {
                    $flat[] = (is_string($key) ? \Illuminate\Support\Str::headline($key) . ': ' : '') . (is_bool($item) ? ($item ? 'Yes' : 'No') : $item);
                }
            });

            return $flat ? trim(strip_tags(implode(' · ', $flat))) : '—';
        }

        return (string) $v;
    }

    public static function has(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
