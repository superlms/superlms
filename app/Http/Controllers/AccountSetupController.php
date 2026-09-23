<?php

namespace App\Http\Controllers;

use App\Models\AccountSetupLink;
use App\Models\Organization;
use App\Models\Student\StudentDetail;

/**
 * GET /account-setup/{token} — the page a new student's or teacher's WhatsApp
 * "View details" button opens: who they are, what they sign in with, their
 * password, and the app to download.
 *
 * The password shows only while the link is fresh (AccountSetupLink::DAYS)
 * and the account still has the password it was given; after that the page
 * points them to Forgot password in the app instead.
 */
class AccountSetupController extends Controller
{
    public function show(string $token)
    {
        $link = AccountSetupLink::with('user')->where('token_hash', hash('sha256', $token))->first();
        $user = $link?->user;

        $data = ['state' => 'invalid', 'appUrl' => config('services.whatsapp.app_url')];

        if ($link && $user) {
            $org     = Organization::find($user->organization_id);
            $student = $user->role === 'user'
                ? StudentDetail::with(['standard', 'section'])->where('user_id', $user->id)->first()
                : null;

            $data += [
                'name'       => $student?->full_name ?: $user->name,
                'schoolName' => $org?->name,
                'schoolLogo' => $org?->logo,
                'class'      => $student
                    ? trim(($student->standard->name ?? '') . ($student->section ? ' - ' . $student->section->name : ''))
                    : null,
                'idLabel'    => $student ? 'Admission number' : 'Username',
                'idValue'    => $student ? $student->admission_no : $user->username,
            ];

            if ($link->expired()) {
                $data['state'] = 'expired';
            } elseif (!$link->passwordUnchanged() || !($password = $user->plainPassword())) {
                $data['state'] = 'changed';
            } else {
                $data['state']    = 'ready';
                $data['password'] = $password;
                if (!$link->opened_at) {
                    $link->forceFill(['opened_at' => now()])->save();
                }
            }
        }

        return response()
            ->view('account-setup', $data, $data['state'] === 'invalid' ? 404 : 200)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
