<?php

namespace App\Support;

use App\Models\Biometric\BiometricDevice;
use App\Models\Biometric\BiometricPunch;
use App\Models\Biometric\BiometricTemplate;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Services\AppPushNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What a punch does to the day.
 *
 * Student: no punch-in or out — one time a day counts, the first. By the
 * school's last time (Setting → Biometric) it is Present; after it, Absent
 * ("Late punch 09:12 — after 09:00").
 * Teacher: the first punch of the day is the punch-in, the last (two minutes
 * on or more) the punch-out; the day is Present, with "late" / "early" said
 * against the school's times when it has set them.
 *
 * A day marked by hand as Present, Half Day or Holiday is left as it is (the
 * punch time is still kept); a day marked Absent, or not marked, follows the
 * machine. Punches may come late and out of order (a machine that was offline
 * sends them when it is back), so the day is worked out from all of that
 * person's punches each time. The student or teacher hears it on the app
 * straight after the machine is answered, and an Attendance page open on the
 * web picks it up (cache "biometric:last:{org}").
 */
class BiometricPunches
{
    private const PRESENT = 1;
    private const ABSENT  = 0;
    private const HALF    = 2;
    private const HOLIDAY = [3, 4];   // a holiday; one the app saved

    /** A teacher's second punch closer than this to the first is the same punch. */
    private const SAME_PUNCH_MINUTES = 2;

    /** The machine's verify codes, as ZKTeco-style push sends them. */
    private const VERIFY = [0 => 'password', 1 => 'fingerprint', 2 => 'card', 3 => 'password', 4 => 'card', 15 => 'face', 25 => 'palm'];

    /**
     * ATTLOG lines ("PIN\tYYYY-MM-DD HH:MM:SS\tstatus\tverify…") from a
     * machine: each new punch is kept and marks its day. How many lines were taken.
     */
    public static function receive(BiometricDevice $device, string $body): int
    {
        $taken = 0;
        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            $f = explode("\t", trim($line));
            if (count($f) < 2 || $f[0] === '') {
                continue;
            }
            try {
                $at = Carbon::createFromFormat('Y-m-d H:i:s', trim($f[1]), config('app.timezone'));
            } catch (\Throwable $e) {
                continue;
            }
            if (!$at) {
                continue;
            }
            $taken++;

            $punch = BiometricPunch::firstOrCreate(
                ['biometric_device_id' => $device->id, 'pin' => trim($f[0]), 'punched_at' => $at],
                ['organization_id' => $device->organization_id, 'verify' => self::VERIFY[(int) ($f[3] ?? -1)] ?? 'other']
            );
            if ($punch->wasRecentlyCreated) {
                $punch->update(['result' => self::apply($punch)]);
            }
        }

        return $taken;
    }

    /** One punch onto its day; what it did (present, late, in, out, repeat, unknown, off, holiday). */
    public static function apply(BiometricPunch $punch): string
    {
        $orgId  = (int) $punch->organization_id;
        $holder = Biometric::holderOfPin($punch->pin);
        $person = $holder ? Biometric::holder($orgId, $holder[0], $holder[1]) : null;
        if (!$person) {
            return 'unknown';
        }
        $punch->forceFill(['holder_type' => $holder[0], 'holder_id' => $holder[1]])->save();

        if (!Biometric::enabled($orgId)) {
            return 'off';
        }

        cache()->put('biometric:last:' . $orgId, $punch->id, now()->addDay());

        return $person instanceof TeacherDetail
            ? self::teacherDay($orgId, $person, $punch)
            : self::studentDay($orgId, $person, $punch);
    }

    private static function studentDay(int $orgId, StudentDetail $student, BiometricPunch $punch): string
    {
        $date  = $punch->punched_at->toDateString();
        $first = self::punchesOn('student', (int) $student->id, $date)->first()->punched_at;
        $last  = Biometric::times($orgId)['student_last_time'];
        $hm    = $first->format('H:i');
        $onTime = $hm <= $last;

        $rec = StudentAttendance::where('organization_id', $orgId)->where('student_detail_id', $student->id)
            ->whereDate('attendance_date', $date)->first();

        if ($rec && in_array((int) $rec->status, self::HOLIDAY, true)) {
            return 'holiday';
        }
        // Marked by hand as Present or Half Day: kept, with the time it was punched.
        if ($rec && $rec->source !== 'biometric' && in_array((int) $rec->status, [self::PRESENT, self::HALF], true)) {
            if (!$rec->punch_time) {
                $rec->update(['punch_time' => $first->format('H:i:s')]);
            }
            return 'repeat';
        }

        $status = $onTime ? self::PRESENT : self::ABSENT;
        $remark = $onTime ? "Punched at {$hm}" : "Late punch {$hm} — after {$last}";
        $before = $rec ? [(int) $rec->status, (string) $rec->punch_time] : null;

        // The day's row found above (by its date) is the one written — never a second.
        $values = [
            'user_id'    => (int) ($student->user_id ?? 0),
            'status'     => $status,
            'remarks'    => $remark,
            'marked_by'  => 0,
            'punch_time' => $first->format('H:i:s'),
            'source'     => 'biometric',
        ];
        $rec ? $rec->update($values)
             : StudentAttendance::create(['organization_id' => $orgId, 'student_detail_id' => $student->id, 'attendance_date' => $date] + $values);

        if ($before === [$status, $first->format('H:i:s')]) {
            return 'repeat';   // a later punch of a day already marked so
        }

        if ($student->user_id && $first->isSameDay(now())) {
            $name = Biometric::holderName($student);
            self::tell((int) $student->user_id, 'Attendance', $onTime
                ? "{$name} reached school at {$hm} — marked Present."
                : "{$name} punched at {$hm}, after {$last} — marked Absent.");
        }

        return $onTime ? 'present' : 'late';
    }

    private static function teacherDay(int $orgId, TeacherDetail $teacher, BiometricPunch $punch): string
    {
        $date    = $punch->punched_at->toDateString();
        $punches = self::punchesOn('teacher', (int) $teacher->id, $date);
        $in      = $punches->first()->punched_at;
        $outAt   = $punches->last()->punched_at;
        $out     = $outAt->diffInMinutes($in, true) >= self::SAME_PUNCH_MINUTES ? $outAt : null;
        $times   = Biometric::times($orgId);

        $rec = TeacherAttendance::where('organization_id', $orgId)->where('teacher_detail_id', $teacher->id)
            ->whereDate('attendance_date', $date)->first();
        if ($rec && in_array((int) $rec->status, self::HOLIDAY, true)) {
            return 'holiday';
        }

        $remark = 'In ' . $in->format('H:i');
        if ($times['teacher_in_time'] && $in->format('H:i') > $times['teacher_in_time']) {
            $remark .= ' (late)';
        }
        if ($out) {
            $remark .= ' · Out ' . $out->format('H:i');
            if ($times['teacher_out_time'] && $out->format('H:i') < $times['teacher_out_time']) {
                $remark .= ' (early)';
            }
        }

        $before = $rec ? [(string) $rec->punch_in, (string) $rec->punch_out] : null;
        $now    = [$in->format('H:i:s'), $out?->format('H:i:s') ?? ''];
        // Half Day marked by hand stays; otherwise the punch says Present.
        $status = $rec && $rec->source !== 'biometric' && (int) $rec->status === self::HALF ? self::HALF : self::PRESENT;

        $values = [
            'status'    => $status,
            'remarks'   => $remark,
            'marked_by' => 0,
            'punch_in'  => $now[0],
            'punch_out' => $out ? $now[1] : null,
            'source'    => 'biometric',
        ];
        $rec ? $rec->update($values)
             : TeacherAttendance::create(['organization_id' => $orgId, 'teacher_detail_id' => $teacher->id, 'attendance_date' => $date] + $values);

        if ($before !== null && [$before[0], $before[1]] === $now) {
            return 'repeat';
        }

        $isOut = $out && $punch->punched_at->equalTo($outAt);
        if ($teacher->user_id && $punch->punched_at->isSameDay(now()) && ($isOut || $punch->punched_at->equalTo($in))) {
            self::tell((int) $teacher->user_id, 'Attendance', $isOut
                ? 'Punched out at ' . $out->format('H:i') . '.'
                : 'Punched in at ' . $in->format('H:i') . ' — marked Present.');
        }

        return $isOut ? 'out' : ($punch->punched_at->equalTo($in) ? 'in' : 'repeat');
    }

    /** That person's punches on the day, earliest first. */
    private static function punchesOn(string $type, int $id, string $date)
    {
        return BiometricPunch::where('holder_type', $type)->where('holder_id', $id)
            ->whereBetween('punched_at', [$date . ' 00:00:00', $date . ' 23:59:59'])
            ->orderBy('punched_at')->get(['id', 'punched_at']);
    }

    /** The app notice goes from the queue worker — the machine waits for none of it. */
    private static function tell(int $userId, string $title, string $body): void
    {
        dispatch(fn () => app(AppPushNotifier::class)->biometricPunch($userId, $title, $body));
    }

    /**
     * OPERLOG lines: a fingerprint enrolled on a machine ("FP PIN=…\tFID=…\tTMP=…")
     * is kept and copied to the school's other machines; a card given to a user
     * on the machine itself ("USER PIN=…\tCard=…") becomes theirs here too.
     */
    public static function operations(BiometricDevice $device, string $body): int
    {
        $orgId = (int) $device->organization_id;
        $taken = 0;

        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            $line = trim($line);
            if (!preg_match('/^(FP|USER)\s+(.*)$/', $line, $m)) {
                continue;
            }
            $taken++;
            $kv = [];
            foreach (explode("\t", $m[2]) as $pair) {
                [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
                $kv[strtoupper(trim($k))] = trim($v);
            }
            $pin = $kv['PIN'] ?? '';
            if (!Biometric::holderOfPin($pin)) {
                continue;
            }

            if ($m[1] === 'FP' && ($kv['TMP'] ?? '') !== '') {
                $fp = BiometricTemplate::updateOrCreate(
                    ['organization_id' => $orgId, 'pin' => $pin, 'fid' => (int) ($kv['FID'] ?? 0)],
                    ['size' => (int) ($kv['SIZE'] ?? strlen($kv['TMP'])), 'valid' => (int) ($kv['VALID'] ?? 1), 'template' => $kv['TMP'], 'from_device_id' => $device->id]
                );
                $others = BiometricDevice::where('organization_id', $orgId)->where('id', '!=', $device->id)->pluck('id');
                foreach ($others as $other) {
                    Biometric::queue($orgId, [Biometric::fingerprintCommand($fp)], (int) $other);
                }
            }

            if ($m[1] === 'USER' && ($card = Biometric::cardNumber($kv['CARD'] ?? '')) && $card !== '0') {
                [$type, $id] = Biometric::holderOfPin($pin);
                try {
                    DB::transaction(fn () => Biometric::assignCard($orgId, $type, $id, $card));
                } catch (\Throwable $e) {
                    // The card is someone else's here: the machine's say does not take it from them.
                }
            }
        }

        return $taken;
    }
}
