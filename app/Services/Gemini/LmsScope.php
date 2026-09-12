<?php

namespace App\Services\Gemini;

use App\Models\User;

/**
 * Who is asking, and what they are allowed to see.
 *
 * Every knowledge-pack query and every tool call goes through this object, so
 * there is exactly one place where "which organization" is decided. A school
 * panel user can never widen it: `organizationId` comes from their own user
 * row, not from anything the model or the browser sends.
 */
class LmsScope
{
    public const KIND_SCHOOL   = 'school';
    public const KIND_PLATFORM = 'platform';

    private function __construct(
        public readonly string $kind,
        /** The one organization a school user may ever see. Null for platform. */
        public readonly ?int $organizationId,
        /**
         * Platform users only: an organization they are pinned to, when a
         * sub-super-admin has been limited to a single school. Null means the
         * whole platform.
         */
        public readonly ?int $restrictedOrganizationId,
        public readonly string $role,
        public readonly string $userName,
    ) {}

    public static function for(User $user): ?self
    {
        $role = (string) $user->role;

        if (in_array($role, ['admin', 'sub-admin', 'accounts'], true)) {
            $orgId = (int) ($user->organization_id ?? 0);

            return $orgId > 0
                ? new self(self::KIND_SCHOOL, $orgId, null, $role, (string) $user->name)
                : null;
        }

        if (in_array($role, ['super-admin', 'sub-super-admin'], true)) {
            // EnsureIsSuperAdmin adds global scopes for a pinned sub-super-admin,
            // but only to the handful of models its own screens use. The tools
            // read further than that, so carry the restriction here and apply it
            // explicitly on every query rather than trusting those scopes.
            return new self(
                self::KIND_PLATFORM,
                null,
                $role === 'sub-super-admin' ? $user->allowedOrganizationId() : null,
                $role,
                (string) $user->name,
            );
        }

        return null;
    }

    /**
     * The organization every query must be pinned to, or null when the caller
     * may read across the platform. This is the single gate: a school user is
     * always pinned, a pinned sub-super-admin is always pinned, and only a full
     * super-admin comes back null.
     */
    public function forcedOrganizationId(): ?int
    {
        return $this->organizationId ?? $this->restrictedOrganizationId;
    }

    public function readsWholePlatform(): bool
    {
        return $this->isPlatform() && $this->restrictedOrganizationId === null;
    }

    public function isSchool(): bool
    {
        return $this->kind === self::KIND_SCHOOL;
    }

    public function isPlatform(): bool
    {
        return $this->kind === self::KIND_PLATFORM;
    }

    /** Cache key fragment — distinct per scope so packs never cross over. */
    public function key(): string
    {
        if ($this->isSchool()) {
            return 'school-' . $this->organizationId;
        }

        return 'platform-' . $this->role . ($this->restrictedOrganizationId ? '-org' . $this->restrictedOrganizationId : '');
    }

    /**
     * The bucket the daily question allowance is counted against.
     *
     * One school shares one allowance across every one of its panel users —
     * admin, sub-admin and accounts all draw from the same 50. The platform
     * side gets its own bucket, and a sub-super-admin pinned to a school draws
     * from that school's bucket, because their questions read that school's
     * data.
     */
    public function quotaKey(): string
    {
        $orgId = $this->forcedOrganizationId();

        return $orgId ? 'org-' . $orgId : 'platform';
    }
}
