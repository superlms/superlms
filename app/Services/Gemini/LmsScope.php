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
        public readonly ?int $organizationId,
        public readonly string $role,
        public readonly string $userName,
    ) {}

    public static function for(User $user): ?self
    {
        $role = (string) $user->role;

        if (in_array($role, ['admin', 'sub-admin', 'accounts'], true)) {
            $orgId = (int) ($user->organization_id ?? 0);

            return $orgId > 0
                ? new self(self::KIND_SCHOOL, $orgId, $role, (string) $user->name)
                : null;
        }

        if (in_array($role, ['super-admin', 'sub-super-admin'], true)) {
            // A sub-super-admin limited to one school already has global scopes
            // applied by EnsureIsSuperAdmin for this request, so every model
            // query below is narrowed for them automatically.
            return new self(self::KIND_PLATFORM, null, $role, (string) $user->name);
        }

        return null;
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
        return $this->isSchool() ? 'school-' . $this->organizationId : 'platform-' . $this->role;
    }
}
