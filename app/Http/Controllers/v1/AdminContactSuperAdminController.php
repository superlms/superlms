<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\ContactSuperAdmin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The admin app's Contact Admin: the school's messages to the Super Admin, as
 * the web panel's Contact Admin (App\Livewire\Admin\ContactAdmin) has them
 * over the same model — a topic, the message and an optional image or PDF
 * (2 MB), the Super Admin's reply with its own attachment, the last 7, 15 or
 * 30 days, pending or replied. A message can be edited until it is replied
 * to, and deleted with its attachment. The model's own events tell the Super
 * Admins, as they do for the panel.
 *
 *   GET    /admin/contact-super-admin?days=&status=
 *   POST   /admin/contact-super-admin          (multipart: topic, admin_query, image?)
 *   POST   /admin/contact-super-admin/{id}     (multipart update; image? replaces the one there)
 *   DELETE /admin/contact-super-admin/{id}
 */
class AdminContactSuperAdminController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** The panel's route, for a sub-admin's permissions. */
    private const PANEL_ROUTE = 'admin.contact-admin';

    private const RULES = [
        'topic'       => 'required|string|max:255',
        'admin_query' => 'required|string',
        'image'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ];

    private const MESSAGES = [
        'topic.required'       => 'Enter a topic.',
        'topic.max'            => 'The topic may be at most 255 characters.',
        'admin_query.required' => 'Write your message.',
        'image.mimes'          => 'The attachment must be a JPG, PNG or PDF.',
        'image.max'            => 'The attachment must be 2 MB or smaller.',
    ];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        // A sub-admin reaches it as on the panel: only with its permission.
        if (!$user->canAccessAdminRoute(self::PANEL_ROUTE)) {
            return [null, $this->error('You do not have access to Contact Admin.', 403)];
        }
        return [$user, null];
    }

    private function s3Delete(?string $url): void
    {
        if (!$url) return;
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') return;
        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    private function isPdf(?string $url): bool
    {
        return (bool) $url && strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) === 'pdf';
    }

    private function shape(ContactSuperAdmin $c): array
    {
        $replied = (bool) $c->super_admin_reply;

        return [
            'id'                     => $c->id,
            'topic'                  => $c->topic,
            'admin_query'            => $c->admin_query,
            'image_url'              => $c->image,
            'image_is_pdf'           => $this->isPdf($c->image),
            'replied'                => $replied,
            'super_admin_text'       => $c->super_admin_text,
            'super_admin_attachment' => $c->super_admin_attachment,
            'super_admin_attachment_is_pdf' => $this->isPdf($c->super_admin_attachment),
            // The panel shows the reply with when it was last saved.
            'replied_at'             => $replied ? $c->updated_at?->toIso8601String() : null,
            'user_name'              => $c->user->name ?? null,
            'user_email'             => $c->user->email ?? null,
            'organization'           => $c->organization->name ?? null,
            'created_at'             => $c->created_at?->toIso8601String(),
        ];
    }

    private function find(int $orgId, $id): ?ContactSuperAdmin
    {
        return ContactSuperAdmin::with(['user:id,name,email', 'organization:id,name'])
            ->where('organization_id', $orgId)
            ->find($id);
    }

    /**
     * GET /admin/contact-super-admin?days=7|15|30&status=pending|replied
     *
     * The school's messages newest first, as the panel lists them, and their
     * total, pending and replied over the chosen days.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $base = ContactSuperAdmin::where('organization_id', $user->organization_id);
        if ($request->filled('days')) {
            $base->where('created_at', '>=', Carbon::now()->subDays((int) $request->days));
        }

        $q = (clone $base)->with(['user:id,name,email', 'organization:id,name']);
        // super_admin_reply is a boolean — as the panel filters it
        if ($request->status === 'pending') $q->where('super_admin_reply', false);
        if ($request->status === 'replied') $q->where('super_admin_reply', true);

        return $this->success([
            'contacts' => $q->latest()->get()->map(fn ($c) => $this->shape($c))->values(),
            'stats'    => [
                'total'   => (clone $base)->count(),
                'pending' => (clone $base)->where('super_admin_reply', false)->count(),
                'replied' => (clone $base)->where('super_admin_reply', true)->count(),
            ],
        ], 'Messages fetched.');
    }

    /** POST /admin/contact-super-admin (multipart: topic, admin_query, image?) */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, self::RULES, self::MESSAGES)) return $err;

        $data = [
            'user_id'         => $user->id,
            'organization_id' => $user->organization_id,
            'topic'           => $request->topic,
            'admin_query'     => $request->admin_query,
        ];
        if ($request->hasFile('image')) {
            $data['image'] = $this->upload($request);
        }

        $c = ContactSuperAdmin::create($data);

        return $this->success($this->shape($this->find($user->organization_id, $c->id)), 'Message sent to Super Admin successfully!');
    }

    /**
     * POST /admin/contact-super-admin/{id} (multipart: topic, admin_query, image?)
     *
     * As the panel's edit: a new file replaces the one there, else it stays.
     * A message the Super Admin has replied to is no longer edited.
     */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $c = $this->find($user->organization_id, $id);
        if (!$c) return $this->error('Contact message not found!', 404);
        if ($c->super_admin_reply) {
            return $this->error('The Super Admin has replied to this message, so it can no longer be edited.', 422);
        }
        if ($err = $this->validateWith($request, self::RULES, self::MESSAGES)) return $err;

        $data = [
            'user_id'     => $user->id,
            'topic'       => $request->topic,
            'admin_query' => $request->admin_query,
        ];
        if ($request->hasFile('image')) {
            $this->s3Delete($c->image);
            $data['image'] = $this->upload($request);
        }

        $c->fill($data)->save();

        return $this->success($this->shape($this->find($user->organization_id, $c->id)), 'Message updated successfully!');
    }

    /** DELETE /admin/contact-super-admin/{id} — with its attachment */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $c = ContactSuperAdmin::where('organization_id', $user->organization_id)->find($id);
        if (!$c) return $this->error('Message not found!', 404);

        $this->s3Delete($c->image);
        $c->delete();

        return $this->success(null, 'Message deleted successfully!');
    }

    /** Stores the attachment where the panel does, public, and gives its URL. */
    private function upload(Request $request): string
    {
        $path = $request->file('image')->store('admin/contact/images', 's3');
        Storage::disk('s3')->setVisibility($path, 'public');

        return Storage::disk('s3')->url($path);
    }
}
