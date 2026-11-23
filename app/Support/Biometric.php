<?php

namespace App\Support;

use App\Models\Biometric\BiometricCard;
use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Models\Biometric\BiometricSetting;
use App\Models\Biometric\BiometricTemplate;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Biometric attendance for a school — turned on by the super admin (with a
 * code emailed to them, as Custom Fee), its times set on the admin's Setting
 * page, its machines added by serial number.
 *
 * On a machine every student and teacher is a user with a number of their
 * own (the PIN): "1" then the student's id, "2" then the teacher's — so a
 * punch says who it is without any list to keep in step. A student or
 * teacher's 125 kHz card is assigned on their View panel (tapped on a USB
 * reader) and sent to the school's machines with them, so the machine knows
 * the card; a fingerprint is enrolled on a machine and copied to the others.
 */
class Biometric
{
    public const HOLDERS = ['student', 'teacher'];

    /** A student's punch after this, with none set, is Absent. */
    public const DEFAULT_STUDENT_LAST = '09:00';

    // ── On / off ────────────────────────────────────────────────────────────

    /** Whether the tables are there (before the migration, nothing is on). */
    public static function tracked(): bool
    {
        $key = 'biometric.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('biometric_settings');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    public static function enabled(?int $orgId): bool
    {
        if (!$orgId || !self::tracked()) {
            return false;
        }

        $key = 'biometric.enabled.' . $orgId;
        if (!app()->bound($key)) {
            app()->instance($key, (bool) BiometricSetting::where('organization_id', $orgId)->value('enabled'));
        }

        return (bool) app($key);
    }

    public static function setEnabled(int $orgId, bool $on, ?int $userId = null): void
    {
        if (!self::tracked()) {
            return;
        }

        BiometricSetting::updateOrCreate(['organization_id' => $orgId], ['enabled' => $on, 'updated_by' => $userId]);
        app()->instance('biometric.enabled.' . $orgId, $on);
    }

    // ── The school's times ──────────────────────────────────────────────────

    /** ['student_last_time' => 'H:i', 'teacher_in_time' => 'H:i'|null, 'teacher_out_time' => 'H:i'|null] */
    public static function times(int $orgId): array
    {
        $row = self::tracked() ? BiometricSetting::where('organization_id', $orgId)->first() : null;
        $hm  = fn ($t) => $t ? substr((string) $t, 0, 5) : null;

        return [
            'student_last_time' => $hm($row?->student_last_time) ?? self::DEFAULT_STUDENT_LAST,
            'teacher_in_time'   => $hm($row?->teacher_in_time),
            'teacher_out_time'  => $hm($row?->teacher_out_time),
        ];
    }

    public static function saveTimes(int $orgId, array $times, ?int $userId = null): void
    {
        $clean = fn ($t) => is_string($t) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) ? $t . ':00' : null;

        BiometricSetting::updateOrCreate(['organization_id' => $orgId], [
            'student_last_time' => $clean($times['student_last_time'] ?? null) ?? self::DEFAULT_STUDENT_LAST . ':00',
            'teacher_in_time'   => $clean($times['teacher_in_time'] ?? null),
            'teacher_out_time'  => $clean($times['teacher_out_time'] ?? null),
            'updated_by'        => $userId,
        ]);
    }

    // ── What a first punch marks (Attendance → Timing) ──────────────────────

    /** The statuses that have times, as attendance stores them. */
    public const STATUS = ['present' => 1, 'absent' => 0, 'half_day' => 2];

    /** What the tab starts on until the school saves its own. */
    public const SUGGESTED_TIMES = [
        'present'  => ['start' => '07:00', 'end' => '08:30'],
        'absent'   => ['start' => '11:01', 'end' => '18:00'],
        'half_day' => ['start' => '08:31', 'end' => '11:00'],
    ];

    /** The school's saved teacher times, by status — empty until it has saved any. */
    public static function teacherStatusTimes(int $orgId): array
    {
        return self::savedStatusTimes($orgId, 'teacher_status_times');
    }

    /** The school's saved student times, by status — empty until it has saved any. */
    public static function studentStatusTimes(int $orgId): array
    {
        return self::savedStatusTimes($orgId, 'student_status_times');
    }

    private static function savedStatusTimes(int $orgId, string $column): array
    {
        if (!self::tracked()) {
            return [];
        }

        return (array) (BiometricSetting::where('organization_id', $orgId)->first()?->{$column} ?? []);
    }

    /** What is wrong with the times, in a line for the admin, or null. */
    public static function checkStatusTimes(array $rows): ?string
    {
        $names = ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day'];
        $used  = [];

        foreach (self::STATUS as $key => $_) {
            $start = trim((string) ($rows[$key]['start'] ?? ''));
            $end   = trim((string) ($rows[$key]['end'] ?? ''));
            if ($start === '' && $end === '') {
                continue;
            }
            if ($start === '' || $end === '') {
                return $names[$key] . ': enter both the start and the end time.';
            }
            foreach ([$start, $end] as $t) {
                if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
                    return $names[$key] . ': that is not a time.';
                }
            }
            if ($start >= $end) {
                return $names[$key] . ': the end time must be after the start time.';
            }
            $used[] = [$start, $end, $names[$key]];
        }

        usort($used, fn ($a, $b) => strcmp($a[0], $b[0]));
        for ($i = 1; $i < count($used); $i++) {
            if ($used[$i][0] < $used[$i - 1][1]) {
                return $used[$i - 1][2] . ' and ' . $used[$i][2] . ' overlap: a time can have one status only.';
            }
        }

        return null;
    }

    public static function saveTeacherStatusTimes(int $orgId, array $rows, ?int $userId = null): void
    {
        self::saveStatusTimes($orgId, 'teacher_status_times', $rows, $userId);
    }

    public static function saveStudentStatusTimes(int $orgId, array $rows, ?int $userId = null): void
    {
        self::saveStatusTimes($orgId, 'student_status_times', $rows, $userId);
    }

    private static function saveStatusTimes(int $orgId, string $column, array $rows, ?int $userId): void
    {
        $clean = [];
        foreach (self::STATUS as $key => $_) {
            $start = trim((string) ($rows[$key]['start'] ?? ''));
            $end   = trim((string) ($rows[$key]['end'] ?? ''));
            if ($start !== '' && $end !== '') {
                $clean[$key] = ['start' => $start, 'end' => $end];
            }
        }

        BiometricSetting::updateOrCreate(['organization_id' => $orgId], [
            $column      => $clean ?: null,
            'updated_by' => $userId,
        ]);
    }

    /**
     * The status for a first punch at "H:i", or null when it falls in none of
     * the times. Both ends count; on a shared minute the earlier row has it.
     */
    public static function statusAt(array $times, string $hm): ?int
    {
        uasort($times, fn ($a, $b) => strcmp($a['start'], $b['start']));
        foreach ($times as $key => $row) {
            if (isset(self::STATUS[$key]) && $hm >= $row['start'] && $hm <= $row['end']) {
                return self::STATUS[$key];
            }
        }

        return null;
    }

    // ── Who a machine's number is ───────────────────────────────────────────

    /** The number a student or teacher goes by on the machines. */
    public static function pin(string $type, int $id): string
    {
        return ($type === 'teacher' ? '2' : '1') . $id;
    }

    /** [type, id] of a machine's number, or null when it is none of ours. */
    public static function holderOfPin(string $pin): ?array
    {
        if (!preg_match('/^([12])([1-9]\d{0,11})$/', trim($pin), $m)) {
            return null;
        }

        return [$m[1] === '2' ? 'teacher' : 'student', (int) $m[2]];
    }

    /** The student or teacher of the school, or null. */
    public static function holder(int $orgId, string $type, int $id): StudentDetail|TeacherDetail|null
    {
        $model = $type === 'teacher' ? TeacherDetail::class : StudentDetail::class;

        return $model::with('user:id,name,image,is_active')->where('organization_id', $orgId)->find($id);
    }

    public static function holderName(StudentDetail|TeacherDetail $holder): string
    {
        return (string) ($holder instanceof StudentDetail
            ? ($holder->full_name ?: ($holder->user->name ?? ''))
            : ($holder->user->name ?? ''));
    }

    // ── Cards ───────────────────────────────────────────────────────────────

    /**
     * A card number as a USB reader types it, the way it is kept: digits
     * without their leading zeros (the reader's "0012345678" is the machine's
     * 12345678), anything else upper case without spaces. Null when it cannot be a card.
     */
    public static function cardNumber(?string $raw): ?string
    {
        $c = strtoupper(preg_replace('/[\s\-:]+/', '', (string) $raw));
        if ($c === '' || !preg_match('/^[0-9A-F]{4,20}$/', $c)) {
            return null;
        }
        if (ctype_digit($c)) {
            $c = ltrim($c, '0') ?: '0';
        }

        return $c;
    }

    public static function cardOf(string $type, int $id): ?string
    {
        return self::tracked()
            ? BiometricCard::where('holder_type', $type)->where('holder_id', $id)->value('card_number')
            : null;
    }

    /**
     * Give the card to the student or teacher (in place of the one they had)
     * and send it to the school's machines. A card another person has is not
     * taken from them: the message says whose it is.
     *
     * @throws \RuntimeException
     */
    public static function assignCard(int $orgId, string $type, int $id, ?string $raw, ?int $userId = null): string
    {
        $card = self::cardNumber($raw);
        if ($card === null) {
            throw new \RuntimeException('That is not a card number — tap the card on the reader again.');
        }
        $holder = self::holder($orgId, $type, $id) ?? throw new \RuntimeException('Not found.');

        $other = BiometricCard::where('organization_id', $orgId)->where('card_number', $card)->first();
        if ($other && !($other->holder_type === $type && (int) $other->holder_id === $id)) {
            $owner = self::holder($orgId, $other->holder_type, (int) $other->holder_id);
            throw new \RuntimeException('This card is already with ' . ($owner ? self::holderName($owner) : 'someone else')
                . ' (' . $other->holder_type . '). Remove it there first.');
        }

        BiometricCard::updateOrCreate(
            ['holder_type' => $type, 'holder_id' => $id],
            ['organization_id' => $orgId, 'card_number' => $card, 'assigned_by' => $userId]
        );
        self::queueHolder($orgId, $holder);

        return $card;
    }

    public static function removeCard(int $orgId, string $type, int $id): void
    {
        BiometricCard::where('organization_id', $orgId)->where('holder_type', $type)->where('holder_id', $id)->delete();
        BiometricTemplate::where('organization_id', $orgId)->where('pin', self::pin($type, $id))
            ->where('fid', BiometricFk::CARD_BACKUP)->where('template', 'like', BiometricFk::PREFIX . '%')->delete();

        if ($holder = self::holder($orgId, $type, $id)) {
            // An FK machine takes the user off first, then back with their
            // fingers and without the card: an update alone may leave it there.
            $fk = BiometricDevice::where('organization_id', $orgId)->where('protocol', 'fk')->get();
            foreach ($fk as $device) {
                $number = BiometricFk::machineNumber($device, self::pin($type, $id));
                self::queue($orgId, [BiometricFk::deleteUserCommand($number)], (int) $device->id);
            }
            self::queueHolder($orgId, $holder);
        }
    }

    /** Whether the school has a machine on which cards are tapped (SecurEye-style, FK push). */
    public static function hasFkMachine(int $orgId): bool
    {
        return self::tracked() && BiometricDevice::where('organization_id', $orgId)->where('protocol', 'fk')->exists();
    }

    // ── The machines' work ──────────────────────────────────────────────────

    /** The machine's line that adds (or updates) a student or teacher, with their card. */
    public static function userCommand(StudentDetail|TeacherDetail $holder, ?string $card = null): string
    {
        $type = $holder instanceof TeacherDetail ? 'teacher' : 'student';
        $name = trim(preg_replace('/[^A-Za-z0-9 .\'-]+/', ' ', Str::ascii(self::holderName($holder))));

        return "DATA UPDATE USERINFO PIN=" . self::pin($type, (int) $holder->id)
            . "\tName=" . Str::limit($name, 24, '')
            . "\tPri=0\tPasswd=\tCard=" . ($card ?? self::cardOf($type, (int) $holder->id) ?? '')
            . "\tGrp=1\tTZ=0000000100000000";
    }

    /** Hand the commands to one machine, or to all of the school's. */
    public static function queue(int $orgId, array $commands, ?int $deviceId = null): void
    {
        if (!$commands || !self::tracked()) {
            return;
        }

        $devices = BiometricDevice::where('organization_id', $orgId)
            ->when($deviceId, fn ($q) => $q->whereKey($deviceId))
            ->pluck('id');
        $now  = now();
        $rows = [];
        foreach ($devices as $d) {
            foreach ($commands as $c) {
                $rows[] = ['biometric_device_id' => $d, 'command' => $c, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            BiometricCommand::insert($chunk);
        }
    }

    /** The student or teacher (and their card) onto the school's machines. */
    public static function queueHolder(int $orgId, StudentDetail|TeacherDetail $holder): void
    {
        self::queue($orgId, [self::userCommand($holder)]);
    }

    /**
     * Everyone of the school onto one machine — the students and teachers
     * whose accounts are active, each with their card, and every fingerprint
     * kept — in place of anything it was still waiting for. How many users.
     */
    public static function queueEveryone(BiometricDevice $device, bool $afterWhatIsQueued = false): int
    {
        $orgId = (int) $device->organization_id;
        if ($device->protocol === 'fk') {
            BiometricFk::release($device);
        }
        if (!$afterWhatIsQueued) {
            BiometricCommand::where('biometric_device_id', $device->id)->where('status', 'pending')->delete();
        }

        $cards = BiometricCard::where('organization_id', $orgId)->get()
            ->mapWithKeys(fn ($c) => [$c->holder_type . ':' . $c->holder_id => $c->card_number]);
        $active = fn ($q) => $q->where('is_active', 1);

        $commands = [];
        foreach (StudentDetail::with('user:id,name,is_active')->where('organization_id', $orgId)->whereHas('user', $active)->orderBy('id')->get() as $s) {
            $commands[] = self::userCommand($s, $cards['student:' . $s->id] ?? '');
        }
        foreach (TeacherDetail::with('user:id,name,is_active')->where('organization_id', $orgId)->whereHas('user', $active)->orderBy('id')->get() as $t) {
            $commands[] = self::userCommand($t, $cards['teacher:' . $t->id] ?? '');
        }
        $users = count($commands);

        // Fingerprints work only on their own brand. An FK machine gets its
        // own inside each user (BiometricFk::translate); ADMS ones get ADMS ones.
        foreach (BiometricTemplate::where('organization_id', $orgId)->get() as $fp) {
            if ($device->protocol !== 'fk' && !str_starts_with((string) $fp->template, BiometricFk::PREFIX)) {
                $commands[] = self::fingerprintCommand($fp);
            }
        }

        self::queue($orgId, $commands, (int) $device->id);

        return $users;
    }

    public static function fingerprintCommand(BiometricTemplate $fp): string
    {
        return "DATA UPDATE FINGERTMP PIN={$fp->pin}\tFID={$fp->fid}\tSize={$fp->size}\tValid={$fp->valid}\tTMP={$fp->template}";
    }

    /** A machine of the school, added by its serial number — and told who everyone is. */
    public static function addDevice(int $orgId, string $serial, ?string $name = null): BiometricDevice
    {
        $serial = strtoupper(trim($serial));
        if (!preg_match('/^[A-Z0-9_-]{4,64}$/', $serial)) {
            throw new \RuntimeException('Enter the serial number the machine shows (Menu → System Info → Device Info).');
        }
        $taken = BiometricDevice::where('serial_number', $serial)->first();
        if ($taken && (int) $taken->organization_id !== $orgId) {
            throw new \RuntimeException('This machine is already added to another school.');
        }

        $device = BiometricDevice::updateOrCreate(['serial_number' => $serial], [
            'organization_id' => $orgId,
            'name'            => trim((string) $name) ?: ($taken->name ?? null),
        ]);
        self::queueEveryone($device);
        self::forgetUnknown($serial);

        return $device;
    }

    // ── Machines that call without being added ──────────────────────────────

    /** A serial number that called here and is no school's: kept a day, newest first, for the super admin to add. */
    public static function noteUnknown(string $serial, ?string $ip): void
    {
        $seen = (array) cache()->get('biometric:unknown', []);
        $seen[$serial] = ['at' => now()->timestamp, 'ip' => $ip];
        arsort($seen);
        cache()->put('biometric:unknown', array_slice($seen, 0, 20, true), now()->addDay());
    }

    public static function unknown(): array
    {
        return array_filter((array) cache()->get('biometric:unknown', []), fn ($v) => ($v['at'] ?? 0) > now()->subDay()->timestamp);
    }

    private static function forgetUnknown(string $serial): void
    {
        $seen = (array) cache()->get('biometric:unknown', []);
        unset($seen[$serial]);
        cache()->put('biometric:unknown', $seen, now()->addDay());
    }
}
