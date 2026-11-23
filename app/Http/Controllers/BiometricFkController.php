<?php

namespace App\Http\Controllers;

use App\Models\Biometric\BiometricDevice;
use App\Support\Biometric;
use App\Support\BiometricFk;
use App\Support\BiometricPunches;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SecurEye-style machines: one address, the work named in a header.
 *
 *   request_code: realtime_glog         a punch
 *   request_code: realtime_enroll_data  a finger or card enrolled on it
 *   request_code: receive_cmd           anything for me to do?
 *   request_code: send_cmd_result       how that went
 *
 * The machine posts to "//" and sets the headers with underscores, which Caddy
 * removes: the box's port 80 is a small nginx gate (infra/lite/edge/default.conf)
 * that sends any call with a request_code header straight here. The machine does
 * not follow redirects, and a body on an acknowledgement makes it send the same
 * record again and again.
 */
class BiometricFkController extends Controller
{
    public function handle(Request $request): Response
    {
        $code  = strtolower(trim((string) $request->server('HTTP_REQUEST_CODE')));
        $trans = substr(trim((string) $request->server('HTTP_TRANS_ID')), 0, 16);

        $device = $this->device($request);
        if (!$device) {
            // Not an answer of OK: the machine keeps its punches until it is added.
            return $this->reply($code === 'receive_cmd' ? 'ERROR_NO_CMD' : 'ERROR', $trans);
        }

        if ($code === 'receive_cmd') {
            $work = BiometricFk::nextWork($device);

            return $work
                ? $this->reply('OK', $work['trans'], $work['code'], $work['body'])
                : $this->reply('ERROR_NO_CMD');
        }

        $body = $this->wholeBody($request, $device, $code, $trans);
        if ($body === null) {
            return $this->reply('OK', $trans);     // one block of several
        }
        [$json, $bins] = BiometricFk::parse($body);

        if ($code === 'realtime_glog') {
            $line = BiometricFk::punchLine($device, $json);
            if ($line !== null) {
                BiometricPunches::receive($device, $line);
            }
            BiometricFk::note($device, $line !== null ? 'punch' : 'punch ignored', $json);
        } elseif ($code === 'realtime_enroll_data') {
            $kept = BiometricFk::keepEnrolment($device, $json, $bins);
            BiometricFk::note($device, 'enrolled (' . $kept . ' kept)', $json, $bins);
        } elseif ($code === 'send_cmd_result') {
            $answer = (string) $request->server('HTTP_CMD_RETURN_CODE');
            BiometricFk::finish($device, $trans, $answer);
            BiometricFk::note($device, 'result ' . $trans . ': ' . $answer, $json, $bins, 160);
        } else {
            BiometricFk::note($device, 'unknown request: ' . substr($code, 0, 40), $json);
        }

        return $this->reply('OK', $trans);
    }

    private function device(Request $request): ?BiometricDevice
    {
        $serial = strtoupper(trim((string) $request->server('HTTP_DEV_ID')));
        if ($serial === '' || !Biometric::tracked()) {
            return null;
        }

        $device = BiometricDevice::where('serial_number', $serial)->first();
        if (!$device) {
            Biometric::noteUnknown($serial, $request->ip());
            return null;
        }

        if ($device->protocol !== 'fk' || !$device->last_seen_at
            || $device->last_seen_at->lt(now()->subSeconds(30)) || $device->last_ip !== $request->ip()) {
            $device->forceFill(['protocol' => 'fk', 'last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
        }

        return $device;
    }

    /** A long message comes in numbered blocks, the last as 0: null until it is all here. */
    private function wholeBody(Request $request, BiometricDevice $device, string $code, string $trans): ?string
    {
        $block = (int) $request->server('HTTP_BLK_NO', 0);
        $key   = 'biometric:fk:blocks:' . $device->id . ':' . md5($code . '|' . $trans);
        $body  = (string) $request->getContent();

        if ($block > 0) {
            $sofar = $block === 1 ? '' : (string) Cache::get($key, '');
            if (strlen($sofar) + strlen($body) <= 8 * 1024 * 1024) {
                Cache::put($key, $sofar . $body, now()->addMinutes(10));
            }

            return null;
        }

        $sofar = Cache::pull($key);

        return $sofar === null ? $body : $sofar . $body;
    }

    private function reply(string $answer, string $trans = '', string $command = '', string $body = ''): Response
    {
        $headers = [
            'Content-Type'   => 'application/octet-stream',
            'Content-Length' => (string) strlen($body),
            'Connection'     => 'close',
            'response_code'  => $answer,
        ];
        if ($trans !== '') {
            $headers['trans_id'] = $trans;
        }
        if ($command !== '') {
            $headers['cmd_code'] = $command;
        }

        return response($body, 200, $headers);
    }
}
