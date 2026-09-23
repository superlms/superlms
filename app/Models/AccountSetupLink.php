<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The link on a new account's WhatsApp welcome — see AccountSetupController.
 * The token itself is only ever in the message; this keeps its hash.
 */
class AccountSetupLink extends Model
{
    /** How long a link keeps working. */
    public const DAYS = 7;

    protected $fillable = ['user_id', 'token_hash', 'password_hash', 'expires_at', 'opened_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'opened_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A new link for this account. Returns the token, which goes in the URL
     * and is not stored.
     */
    public static function issue(User $user): string
    {
        $token = Str::random(40);

        static::create([
            'user_id'       => $user->id,
            'token_hash'    => hash('sha256', $token),
            'password_hash' => $user->password,
            'expires_at'    => now()->addDays(self::DAYS),
        ]);

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token_hash', hash('sha256', $token))->first();
    }

    public function expired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Whether the account still has the password it was given with the link. */
    public function passwordUnchanged(): bool
    {
        return $this->password_hash
            && $this->user
            && hash_equals((string) $this->password_hash, (string) $this->user->password);
    }
}
