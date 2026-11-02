<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\StudentDetail;
use App\Services\FeeAutopay;
use Illuminate\Http\Request;

/**
 * Fee Autopay in the student app (App\Services\FeeAutopay):
 *
 *   GET  /api/v1/fees/autopay         whether the school offers it, the mandate,
 *                                     the installments it will pay and its debits
 *                                     (?refresh=1 asks PhonePe about a setup in progress)
 *   POST /api/v1/fees/autopay/setup   open a mandate → the PhonePe page to approve it on
 *   POST /api/v1/fees/autopay/cancel  stop it
 */
class AutopayController extends ApiController
{
    public function show(Request $request)
    {
        [$student, $err] = $this->student();
        if ($err) return $err;

        if ($request->boolean('refresh') && FeeAutopay::tracked() && ($m = FeeAutopay::liveMandate($student->id)) && $m->status !== 'active') {
            FeeAutopay::syncMandate($m);
        }

        return $this->success(FeeAutopay::summary($student), 'Autopay fetched.');
    }

    public function setup()
    {
        [$student, $err] = $this->student();
        if ($err) return $err;

        try {
            $started = FeeAutopay::start($student, auth()->user());
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'redirect_url' => $started['redirect_url'],
            'autopay'      => FeeAutopay::summary($student),
        ], 'Approve Autopay in PhonePe.');
    }

    public function cancel()
    {
        [$student, $err] = $this->student();
        if ($err) return $err;

        $mandate = FeeAutopay::tracked() ? FeeAutopay::liveMandate($student->id) : null;
        if (!$mandate) {
            return $this->error('Autopay is not on.', 404);
        }

        try {
            FeeAutopay::cancel($mandate);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 502);
        }

        return $this->success(FeeAutopay::summary($student), 'Autopay stopped.');
    }

    /** @return array{0: ?StudentDetail, 1: ?\Illuminate\Http\JsonResponse} */
    private function student(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];

        if ($err = $this->requireRole('user')) return [null, $err];

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        return $student ? [$student, null] : [null, $this->error('Student profile not found.', 404)];
    }
}
