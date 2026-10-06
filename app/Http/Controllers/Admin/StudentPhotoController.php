<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PdfPhotos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A student's photo, from this site.
 *
 *   ?size=96  a small square of it, for the Students list. The photos are
 *             mostly whole camera shots (a few thousand pixels, about a
 *             megabyte each); a page of a hundred of them drawn at 36 px made
 *             the browser decode a hundred big pictures, and the list broke up
 *             while it scrolled. The square is cut once (PdfPhotos, which keeps
 *             it on disk) and the browser keeps it: the list's address carries
 *             the photo's own mark, so a new photo is a new address.
 *   (none)    the photo as it is, for the photo cropper — a picture from this
 *             site can be cut in the browser's canvas, one from the media
 *             host's may not be.
 *
 * Only a student of the signed-in admin's own school (a teacher, for
 * TeacherPhotoController).
 */
class StudentPhotoController extends Controller
{
    private const SIZES = [32, 256];

    /** Whose photos: users.role. */
    protected string $role = 'user';

    public function show(Request $request, $organization, $user)
    {
        $student = User::where('organization_id', Auth::user()->organization_id)
            ->where('role', $this->role)
            ->find((int) $user);
        $url = $student?->image;
        if (!$url) {
            abort(404);
        }
        $url = Str::startsWith($url, ['http://', 'https://']) ? $url : Storage::disk('s3')->url($url);

        $size = (int) $request->query('size', 0);
        if ($size > 0) {
            $size = max(self::SIZES[0], min(self::SIZES[1], $size));
            $jpeg = PdfPhotos::squares([$url], $size, 8.0)[$url] ?? null;

            // Not to be had just now: the photo itself, as the list showed it.
            if ($jpeg === null) {
                return redirect()->away($url);
            }

            return response($jpeg, 200, [
                'Content-Type'  => 'image/jpeg',
                'Cache-Control' => 'private, max-age=31536000, immutable',
            ]);
        }

        try {
            $response = Http::timeout(20)->connectTimeout(3)->get($url);
        } catch (\Throwable $e) {
            abort(404);
        }
        if (!$response->successful()) {
            abort(404);
        }

        return response($response->body(), 200, [
            'Content-Type'  => $response->header('Content-Type') ?: 'image/jpeg',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** The list's address for a student's small photo. */
    public static function thumbUrl(?User $student, int $size = 96): ?string
    {
        if (!$student?->image) {
            return null;
        }

        return route('admin.student.photo', [
            'organization' => $student->organization_id,
            'user'         => $student->id,
            'size'         => $size,
            // A new photo is a new address, so the browser never keeps an old one.
            'v'            => substr(sha1((string) $student->image), 0, 12),
        ]);
    }
}
