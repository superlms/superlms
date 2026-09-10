<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Chat attachments are private: S3 objects in this bucket are not publicly
 * readable (a raw object URL answers "Access Denied"), so every attachment is
 * fetched through here. We check the viewer really is a participant of the
 * conversation and then hand back a short-lived signed URL, falling back to
 * streaming the bytes ourselves if signing isn't available.
 */
class AttachmentController extends Controller
{
    /** The panels share one session; whichever guard is signed in owns the request. */
    protected function viewer()
    {
        foreach (['admin', 'accounts', 'superadmin', 'web'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                return $user;
            }
        }
        return null;
    }

    public function show(Request $request, int $message)
    {
        $viewer = $this->viewer();
        abort_if(!$viewer, 403);

        $msg = Message::find($message);
        abort_if(!$msg || !$msg->attachment_url, 404);

        $isParticipant = $msg->conversation
            && $msg->conversation->participants()->where('user_id', $viewer->id)->exists();
        abort_if(!$isParticipant, 403);

        // Someone who deleted the message for themselves shouldn't reach its file.
        $deleted = \Illuminate\Support\Facades\DB::table('chat_message_deletes')
            ->where('message_id', $msg->id)->where('user_id', $viewer->id)->exists();
        abort_if($deleted, 404);

        $path = $this->objectPath($msg);
        abort_if($path === null, 404);

        $name     = $msg->attachment_name ?: basename($path);
        $download = $request->boolean('download');
        $disk     = Storage::disk('s3');

        try {
            return redirect()->away($disk->temporaryUrl($path, now()->addMinutes(10), [
                'ResponseContentDisposition' => ($download ? 'attachment' : 'inline')
                    . '; filename="' . addslashes($name) . '"',
            ]));
        } catch (\Throwable $e) {
            // Signing unavailable (or a non-S3 driver) — stream it instead.
        }

        abort_if(!$disk->exists($path), 404);

        return new StreamedResponse(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => ($download ? 'attachment' : 'inline')
                . '; filename="' . addslashes($name) . '"',
            'Cache-Control'       => 'private, max-age=600',
        ]);
    }

    /**
     * The S3 key for a message. Newer messages store it directly; older ones
     * only kept the full URL, so peel the disk's base URL off the front.
     */
    protected function objectPath(Message $msg): ?string
    {
        if ($msg->attachment_path) {
            return ltrim($msg->attachment_path, '/');
        }

        $url = (string) $msg->attachment_url;
        $key = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($key === '') {
            return null;
        }

        // Path-style endpoints put the bucket first: /bucket/chat/attachments/x.
        $bucket = (string) config('filesystems.disks.s3.bucket');
        if ($bucket !== '' && str_starts_with($key, $bucket . '/')) {
            $key = substr($key, strlen($bucket) + 1);
        }

        return rawurldecode($key);
    }
}
