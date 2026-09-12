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
 *
 * It carries the second gate too: the MODULES this login may read, worked out
 * from its role and — for a sub-admin — from the screens it was actually
 * granted. Tools, record types and even the sections of the knowledge pack are
 * filtered through it, so the assistant can never read out a number from a
 * screen the user cannot open.
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
        /** @var array<int,string> Module slugs from {@see LmsAccess}. */
        public readonly array $modules,
    ) {}

    public static function for(User $user): ?self
    {
        $role    = (string) $user->role;
        $modules = LmsAccess::modulesFor($user);

        if (in_array($role, ['admin', 'sub-admin', 'accounts'], true)) {
            $orgId = (int) ($user->organization_id ?? 0);

            return $orgId > 0
                ? new self(self::KIND_SCHOOL, $orgId, null, $role, (string) $user->name, $modules)
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
                $modules,
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

    /** Whether this login may read a module at all. */
    public function can(string $module): bool
    {
        return in_array($module, $this->modules, true);
    }

    /** True when every one of these modules is readable. */
    public function canAll(string ...$modules): bool
    {
        foreach ($modules as $module) {
            if (! $this->can($module)) {
                return false;
            }
        }

        return true;
    }

    /** True when at least one of these modules is readable. */
    public function canAny(string ...$modules): bool
    {
        foreach ($modules as $module) {
            if ($this->can($module)) {
                return true;
            }
        }

        return false;
    }

    /** Modules this login was NOT granted — named in the knowledge pack. */
    public function missingModules(): array
    {
        $all = array_keys(LmsAccess::MODULES);

        if ($this->isSchool()) {
            $all = array_values(array_diff($all, ['platform']));
        }

        return array_values(array_diff($all, $this->modules));
    }

    /** Whether this login is held to a subset of its panel's screens. */
    public function isRestricted(): bool
    {
        return $this->missingModules() !== [];
    }

    /**
     * Cache key fragment — distinct per scope so packs never cross over.
     *
     * The permission set is part of it: two sub-admins of the same school with
     * different screens granted must never share a knowledge pack, or one
     * would be handed the other's fee totals.
     */
    public function key(): string
    {
        $base = $this->isSchool()
            ? 'school-' . $this->organizationId
            : 'platform-' . $this->role . ($this->restrictedOrganizationId ? '-org' . $this->restrictedOrganizationId : '');

        $modules = $this->modules;
        sort($modules);

        return $base . '-' . $this->role . '-' . substr(md5(implode(',', $modules)), 0, 8);
    }

    /**
     * The bucket the daily question allowance is counted against.
     *
     * One bucket is one role inside one school: every admin of a school shares
     * the admin allowance, every sub-admin the sub-admin allowance, and the
     * platform roles have their own. A sub-super-admin pinned to a school still
     * counts against the platform side, because the allowance follows the
     * login's role, not the data it happens to read.
     */
    public function quotaKey(): string
    {
        $orgId = $this->isSchool() ? $this->organizationId : null;

        return ($orgId ? 'org-' . $orgId : 'platform') . ':' . $this->role;
    }
}
