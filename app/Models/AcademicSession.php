<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * An academic session — a school year such as 2026–27, from its first day to
 * its last — kept by the super admin on Super Admin → Session. One session is
 * the current one: the top bar of every panel shows its name.
 */
class AcademicSession extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_current'];

    protected $casts = [
        'starts_on'  => 'date',
        'ends_on'    => 'date',
        'is_current' => 'boolean',
    ];

    /** The current session, or null when none is set (or the table isn't there yet). */
    public static function current(): ?self
    {
        return rescue(fn () => static::where('is_current', true)->first(), null, false);
    }

    /**
     * What the top bar shows: the current session's name; with none set, the
     * year it always showed — this year and the next ("2026–27").
     */
    public static function currentLabel(): string
    {
        if ($current = static::current()) {
            return $current->name;
        }

        $year = (int) now()->year;

        return $year . '–' . substr((string) ($year + 1), -2);
    }

    /** "2026–27" for a session that runs into the next year, "2026" within one, "2026–2028" over more. */
    public static function nameFor(CarbonInterface $start, CarbonInterface $end): string
    {
        $from = $start->year;
        $to   = $end->year;

        return match (true) {
            $to <= $from     => (string) $from,
            $to === $from + 1 => $from . '–' . substr((string) $to, -2),
            default          => $from . '–' . $to,
        };
    }

    /** Make this the current session — and no other. */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            static::whereKeyNot($this->getKey())->where('is_current', true)->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();
        });
    }

    /** "Apr 2026 – Mar 2027" */
    public function durationLabel(): string
    {
        return $this->starts_on->format('M Y') . ' – ' . $this->ends_on->format('M Y');
    }

    /** Whole months it spans, counting the first and the last ("12"). */
    public function months(): int
    {
        return (int) round($this->starts_on->diffInMonths($this->ends_on->copy()->addDay()));
    }

    /** 'running' (today falls in it), 'upcoming' or 'completed'. */
    public function status(): string
    {
        $today = now()->startOfDay();

        return match (true) {
            $today->lt($this->starts_on) => 'upcoming',
            $today->gt($this->ends_on)   => 'completed',
            default                      => 'running',
        };
    }
}
