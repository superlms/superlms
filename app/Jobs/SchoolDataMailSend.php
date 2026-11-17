<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\ZeptoMailService;
use App\Support\SchoolPdf\Exporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Super Admin → School Data → Send to Mail, the last step: every PDF the
 * chain made goes to the school's registered email as attachments — over
 * more than one email when together they are too big for one — and the
 * folder is cleared. School Data shows how it went (status()).
 */
class SchoolDataMailSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Attachments per email, before base64 (ZeptoMail takes up to 15 MB a mail). */
    public const PART_BYTES = 9_000_000;

    public int $timeout = 115;

    public int $tries = 2;

    public function __construct(public int $org, public string $dir, public string $to, public array $counts = [])
    {
    }

    /**
     * Start a run: a job per functionality with records, then this one.
     * Returns false when a run for the school is already on its way.
     */
    public static function start(Organization $org, string $to): bool
    {
        if (!Cache::add(self::lockKey($org->id), 1, now()->addMinutes(20))) {
            return false;
        }

        $counts = Exporter::counts($org->id);
        $keys   = array_values(array_filter(array_keys(Exporter::SECTIONS), fn ($k) => Exporter::hasData($org->id, $k, $counts[$k] ?? 0)));
        $dir    = storage_path('app/school-data-mail/' . $org->id . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6)));

        self::setStatus($org->id, ['state' => 'sending', 'to' => $to, 'at' => now()->toIso8601String(), 'files' => count($keys)]);

        $orgId = $org->id;
        Bus::chain(array_merge(
            array_map(fn ($k) => new SchoolDataMailPdf($orgId, $k, $dir), $keys),
            [new self($orgId, $dir, $to, $counts)]
        ))->catch(function (\Throwable $e) use ($orgId, $to, $dir) {
            self::setStatus($orgId, ['state' => 'failed', 'to' => $to, 'at' => now()->toIso8601String(), 'error' => Str::limit($e->getMessage(), 160)]);
            Cache::forget(self::lockKey($orgId));
            File::deleteDirectory($dir);
        })->dispatch();

        return true;
    }

    public function handle(): void
    {
        $this->send();

        File::deleteDirectory($this->dir);
        Cache::forget(self::lockKey($this->org));
    }

    /** Given up after its tries: say so, and clear up. */
    public function failed(?\Throwable $e): void
    {
        self::setStatus($this->org, ['state' => 'failed', 'to' => $this->to, 'at' => now()->toIso8601String(),
            'error' => Str::limit((string) $e?->getMessage(), 160)]);
        File::deleteDirectory($this->dir);
        Cache::forget(self::lockKey($this->org));
    }

    private function send(): void
    {
        $org = Organization::find($this->org);
        if (!$org) {
            return;
        }

        $files = [];
        $failedKeys = [];
        foreach (array_keys(Exporter::SECTIONS) as $key) {
            if (is_file($this->dir . '/' . $key . '.pdf')) {
                $files[$key] = $this->dir . '/' . $key . '.pdf';
            } elseif (is_file($this->dir . '/' . $key . '.failed')) {
                $failedKeys[] = $key;
            }
        }
        $failed = array_map(fn ($k) => Exporter::label($k), $failedKeys);
        $empty  = array_map(fn ($k) => Exporter::label($k), array_values(array_diff(array_keys(Exporter::SECTIONS), array_keys($files), $failedKeys)));

        if (!$files) {
            self::setStatus($this->org, ['state' => 'failed', 'to' => $this->to, 'at' => now()->toIso8601String(), 'error' => 'No PDF could be made.']);

            return;
        }

        // Attachments grouped so that no one email is too big.
        $parts = [[]];
        $size  = 0;
        foreach ($files as $key => $path) {
            $bytes = filesize($path) ?: 0;
            if ($size > 0 && $size + $bytes > self::PART_BYTES) {
                $parts[] = [];
                $size = 0;
            }
            $parts[array_key_last($parts)][$key] = $path;
            $size += $bytes;
        }

        $date = now()->format('d M Y');

        foreach ($parts as $i => $part) {
            $subject = $org->name . ' · School data from SuperLMS · ' . $date . (count($parts) > 1 ? ' (' . ($i + 1) . ' of ' . count($parts) . ')' : '');
            $html    = view('emails.school-data', [
                'school'  => $org->name,
                'date'    => $date,
                'files'   => array_map(fn ($k) => ['name' => Exporter::label($k), 'count' => $this->counts[$k] ?? null], array_keys($part)),
                'part'    => $i + 1,
                'parts'   => count($parts),
                'empty'   => $i === 0 ? $empty : [],
                'failed'  => $i === 0 ? $failed : [],
            ])->render();

            ZeptoMailService::sendRaw($subject, $html, $this->to, $org->name, null, null, array_map(fn ($key, $path) => [
                'content'   => base64_encode((string) file_get_contents($path)),
                'mime_type' => 'application/pdf',
                'name'      => Exporter::filename($org, $key),
            ], array_keys($part), array_values($part)));
        }

        self::setStatus($this->org, ['state' => 'sent', 'to' => $this->to, 'at' => now()->toIso8601String(), 'files' => count($files), 'emails' => count($parts)]);
    }

    // ─── The run's status, read by School Data ────────────────────────────────

    public static function status(int $org): ?array
    {
        return Cache::get(self::statusKey($org));
    }

    private static function setStatus(int $org, array $status): void
    {
        Cache::put(self::statusKey($org), $status, now()->addDays(7));
    }

    private static function statusKey(int $org): string
    {
        return 'school-data-mail:status:' . $org;
    }

    private static function lockKey(int $org): string
    {
        return 'school-data-mail:lock:' . $org;
    }
}
