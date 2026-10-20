<?php

namespace App\Http\Controllers;

use App\Models\Biometric\BiometricCommand;
use App\Models\Biometric\BiometricDevice;
use App\Support\Biometric;
use App\Support\BiometricPunches;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The school's attendance machines talk to us here — the "ADMS" / iclock
 * push protocol of ZKTeco-style machines (Realtime, eSSL and others): the
 * machine calls us, so nothing at the school has to be opened to the internet.
 *
 *   GET  /iclock/cdata?SN=…&options=all   it starts: we answer with how to send
 *   POST /iclock/cdata?SN=…&table=ATTLOG  punches, as they happen (Realtime=1)
 *   POST /iclock/cdata?SN=…&table=OPERLOG users, cards and fingerprints enrolled on it
 *   GET  /iclock/getrequest?SN=…          every few seconds: anything to do? (C:id:command lines)
 *   POST /iclock/devicecmd?SN=…           how those commands went
 *
 * A machine is known by its serial number (Super Admin → School → Biometric);
 * one that is not added gets nothing but "OK", and is listed there to add.
 * Answers are plain text, as the machines read them. Routes: routes/biometric.php,
 * with no session, cookie or CSRF middleware — a machine has none of them.
 */
class BiometricPushController extends Controller
{
    public function handshake(Request $request): Response
    {
        $device = $this->device($request);
        if (!$device) {
            return $this->text('OK');
        }

        return $this->text(implode("\n", [
            'GET OPTION FROM: ' . $device->serial_number,
            'ATTLOGStamp=' . ($device->attlog_stamp ?: 'None'),
            'OPERLOGStamp=' . ($device->operlog_stamp ?: 'None'),
            'ATTPHOTOStamp=None',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog EnrollUser ChgUser EnrollFP ChgFP',
            'Realtime=1',
            'Encrypt=None',
        ]) . "\n");
    }

    public function receive(Request $request): Response
    {
        $device = $this->device($request);
        if (!$device) {
            return $this->text('OK');
        }

        $table = strtoupper((string) $request->query('table', ''));
        $stamp = (string) $request->query('Stamp', '');
        $body  = (string) $request->getContent();

        if ($table === 'ATTLOG') {
            $n = BiometricPunches::receive($device, $body);
            if ($stamp !== '') {
                $device->update(['attlog_stamp' => substr($stamp, 0, 32)]);
            }

            return $this->text('OK: ' . $n);
        }
        if ($table === 'OPERLOG') {
            $n = BiometricPunches::operations($device, $body);
            if ($stamp !== '') {
                $device->update(['operlog_stamp' => substr($stamp, 0, 32)]);
            }

            return $this->text('OK: ' . $n);
        }

        return $this->text('OK');
    }

    /** The machine's next work: up to 30 commands, one a line as "C:id:command". */
    public function commands(Request $request): Response
    {
        $device = $this->device($request);
        if (!$device) {
            return $this->text('OK');
        }

        // Handed out but never answered (the machine went off): handed out again.
        BiometricCommand::where('biometric_device_id', $device->id)->where('status', 'sent')
            ->where('sent_at', '<', now()->subMinutes(10))->update(['status' => 'pending']);

        $next = BiometricCommand::where('biometric_device_id', $device->id)->where('status', 'pending')
            ->orderBy('id')->limit(30)->get(['id', 'command']);
        if ($next->isEmpty()) {
            return $this->text('OK');
        }

        BiometricCommand::whereIn('id', $next->pluck('id'))->update(['status' => 'sent', 'sent_at' => now()]);

        return $this->text($next->map(fn ($c) => 'C:' . $c->id . ':' . $c->command)->implode("\n") . "\n");
    }

    /** "ID=12&Return=0&CMD=DATA" a line: 0 or more is done, below 0 failed. */
    public function results(Request $request): Response
    {
        $device = $this->device($request);
        if (!$device) {
            return $this->text('OK');
        }

        foreach (preg_split('/\r\n|\r|\n/', (string) $request->getContent()) as $line) {
            parse_str(trim($line), $r);
            $id = (int) ($r['ID'] ?? 0);
            if ($id <= 0 || !isset($r['Return'])) {
                continue;
            }
            $code = (int) $r['Return'];
            BiometricCommand::where('biometric_device_id', $device->id)->whereKey($id)->update([
                'status'      => $code >= 0 ? 'done' : 'failed',
                'return_code' => $code,
                'done_at'     => now(),
            ]);
        }

        return $this->text('OK');
    }

    public function ping(): Response
    {
        return $this->text('OK');
    }

    /** The machine calling, by its serial number — and when it was last heard from. */
    private function device(Request $request): ?BiometricDevice
    {
        $serial = strtoupper(trim((string) ($request->query('SN') ?? $request->query('sn') ?? '')));
        if ($serial === '' || !Biometric::tracked()) {
            return null;
        }

        $device = BiometricDevice::where('serial_number', $serial)->first();
        if (!$device) {
            Biometric::noteUnknown($serial, $request->ip());
            return null;
        }

        // Written at most every half minute: it calls every few seconds.
        if (!$device->last_seen_at || $device->last_seen_at->lt(now()->subSeconds(30)) || $device->last_ip !== $request->ip()) {
            $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
        }

        return $device;
    }

    private function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
