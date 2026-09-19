<?php

namespace App\Services\Concerns;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A request's pushes, gathered and sent once it ends.
 *
 * A save often writes many rows (a class's whole timetable, a list of
 * chapters, a sheet of marks), so nothing goes out at once: each person's lines
 * are gathered per subject of the push (its group) and sent as one push when
 * the request ends — only if the transaction they were written in commits —
 * and pushes that read alike go out together. Everything fails open: a push
 * must never break the save behind it.
 */
trait GathersPushes
{
    /** Lines a push lists before "+N more". */
    private const MAX_LINES = 8;

    /** @var array<string, array> one pending push per person and group */
    private array $outbox = [];

    private bool $flushBound = false;

    /**
     * Add lines to a person's pending push for $group (created with $meta the
     * first time). Keyed lines replace each other; it waits for the open
     * transaction to commit and is dropped if it rolls back.
     *
     * @param  array{type:string,title:string,intro:string,screen?:string,params?:array}  $meta
     * @param  array<int|string, string>  $lines
     */
    private function queue(?int $userId, string $group, array $meta, array $lines): void
    {
        if (!$userId) {
            return;
        }
        DB::afterCommit(function () use ($userId, $group, $meta, $lines) {
            $key = "{$userId}|{$group}";
            $entry = $this->outbox[$key] ?? $meta + ['user' => $userId, 'lines' => []];
            foreach ($lines as $k => $line) {
                $entry['lines'][is_int($k) ? $line : $k] = $line;
            }
            $this->outbox[$key] = $entry;

            if (!$this->flushBound) {
                $this->flushBound = true;
                app()->terminating(fn () => $this->flush());
            }
        });
    }

    /** Send what the request gathered — one push per person and group, alike ones together. */
    public function flush(): void
    {
        $outbox = $this->outbox;
        $this->outbox = [];
        $this->flushBound = false;

        $same = [];
        foreach ($outbox as $entry) {
            $lines = $this->pruned($entry['lines']);
            if (!$lines && trim((string) $entry['intro']) === '') {
                continue;
            }
            $more = count($lines) - self::MAX_LINES;
            $lines = array_slice($lines, 0, self::MAX_LINES);
            if ($more > 0) {
                $lines[] = "+{$more} more";
            }
            $payload = [
                'type'   => $entry['type'],
                'title'  => $entry['title'],
                'body'   => Str::limit(trim($entry['intro'] . ($lines ? "\n" . implode("\n", $lines) : '')), 1500),
                'screen' => $entry['screen'] ?? null,
                'params' => $entry['params'] ?? null,
            ];
            $hash = md5(json_encode($payload));
            $same[$hash]['payload'] = $payload;
            $same[$hash]['users'][] = $entry['user'];
        }

        foreach ($same as ['payload' => $payload, 'users' => $users]) {
            try {
                app(FirebaseNotificationService::class)->notifyUserIds($users, $payload['type'], $payload);
            } catch (\Throwable $e) {
                logger()->warning('[push] notification failed (is FIREBASE_CREDENTIALS set?)', [
                    'type'  => $payload['type'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** A chapter's removal says it all — drop the lines about its topics. */
    private function pruned(array $lines): array
    {
        foreach ($lines as $key => $line) {
            if (is_string($key) && preg_match('/^ch:(\d+)$/', $key, $m) && str_starts_with($line, 'Chapter removed')) {
                foreach (array_keys($lines) as $k) {
                    if (is_string($k) && str_starts_with($k, "tp:{$m[1]}:")) {
                        unset($lines[$k]);
                    }
                }
            }
        }

        return array_values($lines);
    }

    // ── Names and values as a push writes them ───────────────────────────────

    private function subjectName(int $id): string
    {
        return Subject::whereKey($id)->value('name') ?: 'Subject';
    }

    private function standardName(int $id): ?string
    {
        return Standard::whereKey($id)->value('name');
    }

    private function sectionName(int $id): ?string
    {
        return $id ? Section::whereKey($id)->value('name') : null;
    }

    /** "10th A" — the class as the app names it, with its section when there is one. */
    private function className(int $standardId, ?int $sectionId): string
    {
        return trim(($this->standardName($standardId) ?? 'Class') . ' ' . ($sectionId ? ($this->sectionName($sectionId) ?? '') : ''));
    }

    private function sectionOrNull($id): ?int
    {
        return (int) $id ?: null;
    }

    private function timeRange($start, $end): string
    {
        $fmt = fn ($t) => $t ? Carbon::parse((string) $t)->format('g:i A') : null;

        return implode(' – ', array_filter([$fmt($start), $fmt($end)]));
    }

    private function dateRange($start, $end): string
    {
        $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d M Y') : null;
        $a = $fmt($start);
        $b = $fmt($end);

        return $a && $b && $a !== $b ? "{$a} – {$b}" : ($a ?? $b ?? '—');
    }

    private function day($value): ?string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** A value as text, so 1 / true / "1" and null / "" read alike. */
    private function norm($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** "₹5,000" / "₹5,000.50" */
    private function rupees($amount): string
    {
        $amount = round((float) $amount, 2);

        return '₹' . number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    /** $fn's result, or null when it throws — a snapshot must never stop the save it is taken for. */
    private function attempt(callable $fn)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            logger()->warning('[push] notification snapshot skipped', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            logger()->warning('[push] notification skipped', ['error' => $e->getMessage()]);
        }
    }
}
