<?php

namespace App\Support;

use App\Models\Biometric\BiometricCard;
use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Models\Biometric\BiometricTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The "FK" push protocol of SecurEye-style machines, beside ADMS.
 *
 * A message body is blocks, each a 4-byte little-endian length and its bytes:
 * first the JSON with a NUL after it (the length counts the NUL), then any
 * binary data the JSON names as "BIN_1", "BIN_2"…
 *
 * The school's work for a machine stays in biometric_commands in the ADMS
 * wording, so everything that queues a person keeps working; it is turned
 * into the FK command when this machine asks for it. One command at a time.
 *
 * Safe first contact: the clock is set, the machine is asked what it holds
 * (read only), and ONE test user is sent. The rest wait until someone has
 * checked that user on the machine and pressed Send everyone. Commands that
 * clear a machine are never sent from here.
 */
class BiometricFk
{
    /** A template kept from an FK machine: only FK machines can use it. */
    public const PREFIX = 'fk:';

    /** Where a machine keeps a card in a user's enroll data: its number as plain digits. */
    public const CARD_BACKUP = 11;

    private const LOG_SIZE = 40;

    // ── Bytes ───────────────────────────────────────────────────────────────

    /** @return array{0: array, 1: array<int, string>} the JSON and its binaries, BIN_1 at key 1 */
    public static function parse(string $body): array
    {
        $blocks = [];
        $at     = 0;
        $len    = strlen($body);
        while ($at + 4 <= $len) {
            $n = unpack('V', substr($body, $at, 4))[1];
            $at += 4;
            if ($n > $len - $at) {
                $blocks = [];
                break;
            }
            $blocks[] = substr($body, $at, $n);
            $at += $n;
        }

        $json = $blocks ? json_decode(rtrim($blocks[0], "\0"), true) : null;
        if (!is_array($json)) {
            // Some firmware sends the JSON alone, with no length before it.
            $plain = json_decode(rtrim($body, "\0\r\n "), true);

            return [is_array($plain) ? $plain : [], []];
        }

        $bins = [];
        foreach (array_slice($blocks, 1) as $i => $bin) {
            $bins[$i + 1] = $bin;
        }

        return [$json, $bins];
    }

    public static function frame(array $json, array $bins = []): string
    {
        $text = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $out  = pack('V', strlen($text) + 1) . $text . "\0";
        foreach ($bins as $bin) {
            $out .= pack('V', strlen($bin)) . $bin;
        }

        return $out;
    }

    // ── Punches ─────────────────────────────────────────────────────────────

    /** "20261123091502" as a time at the school, or null when it is not one. */
    public static function time(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);
        if (!preg_match('/^\d{14}$/', $raw)) {
            return null;
        }

        try {
            $at = Carbon::createFromFormat('YmdHis', $raw, config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }

        return $at && $at->format('YmdHis') === $raw ? $at : null;
    }

    /**
     * Whether a punch may mark a day. A machine keeps what it recorded before
     * it came to this school, and a number there could read as one of our
     * students: nothing from before the day it was added counts, nor a time
     * ahead of today (its clock is wrong).
     */
    public static function counts(BiometricDevice $device, Carbon $at): bool
    {
        $from = ($device->created_at ?? now())->copy()->startOfDay();

        return $at->gte($from) && $at->lte(now()->addDay());
    }

    /** The ADMS line of an FK punch, for BiometricPunches::receive — or null when it must not count. */
    public static function punchLine(BiometricDevice $device, array $json): ?string
    {
        $pin = trim((string) ($json['user_id'] ?? ''));
        $at  = self::time($json['io_time'] ?? null);
        if (!$at || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $pin) || !self::counts($device, $at)) {
            return null;
        }

        return $pin . "\t" . $at->format('Y-m-d H:i:s') . "\t0\t-1\n";
    }

    // ── Enrolments ──────────────────────────────────────────────────────────

    /**
     * What was enrolled on the machine for one of ours, kept as the machine
     * gave it (so it can go back to it, and to the school's other FK machines).
     * How many entries were kept.
     */
    public static function keepEnrolment(BiometricDevice $device, array $json, array $bins): int
    {
        $orgId  = (int) $device->organization_id;
        $pin    = trim((string) ($json['user_id'] ?? ''));
        $holder = Biometric::holderOfPin($pin);
        $person = $holder ? Biometric::holder($orgId, $holder[0], $holder[1]) : null;
        if (!$person) {
            return 0;
        }

        $kept = 0;
        foreach ((array) ($json['enroll_data_array'] ?? []) as $entry) {
            $no  = $entry['backup_number'] ?? null;
            $ref = (string) ($entry['enroll_data'] ?? '');
            if (!is_numeric($no) || !preg_match('/^BIN_(\d+)$/', $ref, $m) || !isset($bins[(int) $m[1]])) {
                continue;
            }
            $bin = $bins[(int) $m[1]];

            // A card tapped on the machine's Register screen is this person's card.
            if ((int) $no === self::CARD_BACKUP && ($card = self::cardFrom($bin)) !== null) {
                if (self::takeCard($orgId, $holder[0], $holder[1], $card)) {
                    BiometricTemplate::where('organization_id', $orgId)->where('pin', $pin)->where('fid', self::CARD_BACKUP)->delete();
                    $kept++;
                }
                continue;
            }

            BiometricTemplate::updateOrCreate(
                ['organization_id' => $orgId, 'pin' => $pin, 'fid' => (int) $no],
                ['size' => strlen($bin), 'valid' => 1, 'template' => self::PREFIX . base64_encode($bin), 'from_device_id' => $device->id]
            );
            $kept++;
        }

        if ($kept) {
            $others = BiometricDevice::where('organization_id', $orgId)->where('id', '!=', $device->id)->pluck('id');
            foreach ($others as $other) {
                Biometric::queue($orgId, [Biometric::userCommand($person)], (int) $other);
            }
        }

        return $kept;
    }

    /** The card number in a machine's enroll data, or null when it is not plain digits. */
    private static function cardFrom(string $bin): ?string
    {
        $digits = rtrim($bin, "\0");

        return ctype_digit($digits) ? Biometric::cardNumber($digits) : null;
    }

    /** The card becomes the person's, unless someone else here already has it. */
    private static function takeCard(int $orgId, string $type, int $id, string $card): bool
    {
        $other = BiometricCard::where('organization_id', $orgId)->where('card_number', $card)->first();
        if ($other && !($other->holder_type === $type && (int) $other->holder_id === $id)) {
            $owner = Biometric::holder($orgId, $other->holder_type, (int) $other->holder_id);
            Cache::put(self::cardErrorKey($type, $id), 'Card ' . $card . ' is already with '
                . ($owner ? Biometric::holderName($owner) : 'someone else') . ' (' . $other->holder_type . '). Remove it there first.', now()->addMinutes(10));

            return false;
        }

        // Touched even when unchanged, so a View panel waiting for this tap sees it arrive.
        BiometricCard::updateOrCreate(
            ['holder_type' => $type, 'holder_id' => $id],
            ['organization_id' => $orgId, 'card_number' => $card]
        )->touch();

        return true;
    }

    public static function cardErrorKey(string $type, int $id): string
    {
        return "biometric:fk:card-error:{$type}:{$id}";
    }

    /** Queued before a user is sent again without their card: updating alone may leave it on the machine. */
    public static function deleteUserCommand(string $pin): string
    {
        return 'FK DELETE_USER ' . $pin;
    }

    // ── The machine's work ──────────────────────────────────────────────────

    /** @return array{trans: string, code: string, body: string}|null the next command, marked as sent */
    public static function nextWork(BiometricDevice $device): ?array
    {
        $id = (int) $device->id;

        if (Cache::get("biometric:fk:busy:{$id}")) {
            return null;     // someone is in the machine's menu
        }

        if (Cache::add("biometric:fk:time:{$id}", 1, now()->addHours(12))) {
            return self::work('T' . now()->timestamp, 'SET_TIME', self::frame(['time' => now()->format('YmdHis')]));
        }
        foreach (['GET_DEVICE_STATUS' => 'S', 'GET_USER_ID_LIST' => 'L'] as $code => $mark) {
            if (Cache::add("biometric:fk:asked:{$code}:{$id}", 1, now()->addDays(30))) {
                return self::work($mark . now()->timestamp, $code, '');
            }
        }

        // Handed out and never answered: out again. One at a time.
        BiometricCommand::where('biometric_device_id', $id)->where('status', 'sent')
            ->where('sent_at', '<', now()->subMinutes(2))->update(['status' => 'pending']);
        if (BiometricCommand::where('biometric_device_id', $id)->where('status', 'sent')->exists()) {
            return null;
        }

        if (!$device->fk_released && self::userCommands($id)->whereIn('status', ['done', 'failed'])->exists()) {
            return null;     // the test user has gone: wait for someone to check it
        }
        if (self::stopped($id)) {
            return null;
        }

        for ($i = 0; $i < 50; $i++) {
            $command = BiometricCommand::where('biometric_device_id', $id)->where('status', 'pending')->orderBy('id')->first();
            if (!$command) {
                return null;
            }

            $work = self::translate($device, (string) $command->command);
            if (!$work) {
                // An ADMS fingerprint: another brand's, of no use to this machine.
                $command->update(['status' => 'done', 'return_text' => 'skipped', 'done_at' => now()]);
                continue;
            }

            $command->update(['status' => 'sent', 'sent_at' => now()]);

            return self::work((string) $command->id, $work['code'], $work['body']);
        }

        return null;
    }

    /** The queued "DATA UPDATE USERINFO …" as SET_USER_INFO, with what this person has enrolled. */
    public static function translate(BiometricDevice $device, string $line): ?array
    {
        if (preg_match('/^FK DELETE_USER ([A-Za-z0-9_-]{1,20})$/', $line, $d)) {
            return ['code' => 'DELETE_USER', 'body' => self::frame(['user_id' => $d[1]])];
        }
        if (!preg_match('/^DATA UPDATE USERINFO (.+)$/s', $line, $m)) {
            return null;
        }

        $kv = [];
        foreach (explode("\t", $m[1]) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $kv[strtoupper(trim($k))] = trim($v);
        }
        $pin = $kv['PIN'] ?? '';
        if ($pin === '') {
            return null;
        }

        // Sent back with the user so that updating a name never loses a finger or card.
        $bins = [];
        $list = [];
        $kept = BiometricTemplate::where('organization_id', $device->organization_id)->where('pin', $pin)
            ->where('template', 'like', self::PREFIX . '%')->orderBy('fid')->get(['fid', 'template']);
        // The card as it is now: this line may have waited in the queue since before it was enrolled.
        $holder = Biometric::holderOfPin($pin);
        $card   = $holder
            ? (string) BiometricCard::where('organization_id', $device->organization_id)
                ->where('holder_type', $holder[0])->where('holder_id', $holder[1])->value('card_number')
            : ($kv['CARD'] ?? '');
        foreach ($kept as $t) {
            if ((int) $t->fid === self::CARD_BACKUP && ctype_digit($card)) {
                continue;     // the person's card, as SuperLMS has it, goes below
            }
            $bins[] = (string) base64_decode(substr($t->template, strlen(self::PREFIX)));
            $list[] = ['backup_number' => (int) $t->fid, 'enroll_data' => 'BIN_' . count($bins)];
        }
        if (ctype_digit($card)) {
            $bins[] = $card;
            $list[] = ['backup_number' => self::CARD_BACKUP, 'enroll_data' => 'BIN_' . count($bins)];
        }

        return [
            'code' => 'SET_USER_INFO',
            'body' => self::frame([
                'user_id'           => $pin,
                'user_name'         => $kv['NAME'] ?? '',
                'user_privilege'    => 'USER',
                'enroll_data_array' => $list,
            ], $bins),
        ];
    }

    /** What the machine said about a command. */
    public static function finish(BiometricDevice $device, string $trans, string $answer): void
    {
        $id     = (int) $device->id;
        $answer = strtoupper(trim($answer));
        if ($answer === 'BUSY') {
            Cache::put("biometric:fk:busy:{$id}", 1, now()->addSeconds(15));
        }

        if (!ctype_digit($trans)) {
            // The clock: tried again soon if it did not take.
            if (str_starts_with($trans, 'T') && $answer !== 'OK') {
                Cache::put("biometric:fk:time:{$id}", 1, now()->addMinutes(10));
            }
            return;
        }

        $command = BiometricCommand::where('biometric_device_id', $id)->whereKey((int) $trans)->where('status', 'sent')->first();
        if (!$command) {
            return;
        }

        if ($answer === 'OK') {
            $command->update(['status' => 'done', 'return_code' => 0, 'return_text' => 'OK', 'done_at' => now()]);
        } elseif ($answer === 'BUSY') {
            $command->update(['status' => 'pending', 'sent_at' => null]);
        } else {
            $command->update(['status' => 'failed', 'return_code' => -1, 'return_text' => substr($answer ?: 'NO ANSWER', 0, 64), 'done_at' => now()]);
        }
    }

    /** Send everyone: a checked test user lets the rest go; what failed is tried afresh. */
    public static function release(BiometricDevice $device): void
    {
        $ok = self::userCommands((int) $device->id)->where('status', 'done')->where('return_text', 'OK')->exists();
        if ($ok && !$device->fk_released) {
            $device->forceFill(['fk_released' => true])->save();
        }

        BiometricCommand::where('biometric_device_id', $device->id)->where('status', 'failed')->delete();
    }

    /** One line for the Super Admin's machine row, or null when all is running. */
    public static function status(BiometricDevice $device): ?string
    {
        $id = (int) $device->id;

        if (!$device->fk_released) {
            $test = self::userCommands($id)->whereIn('status', ['done', 'failed'])->orderBy('id')->first();
            if (!$test) {
                return 'Sending one test user first';
            }
            if ($test->status === 'failed') {
                return 'The machine refused the test user (' . $test->return_text . '). Nothing else was sent';
            }
            preg_match('/PIN=([^\t]*)\tName=([^\t]*)/', (string) $test->command, $m);

            return 'Test user ' . ($m[2] ?? '') . ' (No. ' . ($m[1] ?? '') . ') sent. Check it on the machine, then press Send everyone';
        }

        if (self::stopped($id)) {
            $last = BiometricCommand::where('biometric_device_id', $id)->where('status', 'failed')->orderByDesc('id')->value('return_text');

            return 'Stopped: the machine refused 3 in a row (' . $last . '). Press Send everyone to try again';
        }

        return null;
    }

    /** Three refusals in a row: nothing more until someone looks. */
    private static function stopped(int $id): bool
    {
        $last = BiometricCommand::where('biometric_device_id', $id)->whereIn('status', ['done', 'failed'])
            ->orderByDesc('done_at')->orderByDesc('id')->limit(3)->pluck('status');

        return $last->count() === 3 && $last->every(fn ($s) => $s === 'failed');
    }

    private static function userCommands(int $id)
    {
        return BiometricCommand::where('biometric_device_id', $id)->where('command', 'like', 'DATA UPDATE USERINFO%');
    }

    private static function work(string $trans, string $code, string $body): array
    {
        return ['trans' => $trans, 'code' => $code, 'body' => $body];
    }

    // ── What a machine said lately (to read when something is off) ──────────

    /**
     * Kept a few days per machine. Binary data is only ever measured — a short
     * value (a card) or a command's answer is shown as hex, a fingerprint never.
     */
    public static function note(BiometricDevice $device, string $kind, array $json = [], array $bins = [], int $hex = 16): void
    {
        unset($json['user_photo']);

        $sizes = [];
        foreach ($bins as $k => $bin) {
            $n = strlen($bin);
            $sizes['BIN_' . $k] = $n <= $hex ? $n . ':' . bin2hex($bin) : (string) $n;
        }

        $key = 'biometric:fk:log:' . $device->id;
        $log = (array) Cache::get($key, []);
        $log[] = ['at' => now()->format('Y-m-d H:i:s'), 'kind' => $kind, 'json' => $json, 'bins' => $sizes];
        Cache::put($key, array_slice($log, -self::LOG_SIZE), now()->addDays(3));
    }

    public static function log(BiometricDevice $device): array
    {
        return (array) Cache::get('biometric:fk:log:' . $device->id, []);
    }
}
