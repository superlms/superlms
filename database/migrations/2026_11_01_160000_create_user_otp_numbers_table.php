<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A super admin's numbers for the sign-in code (Super Admin → Profile →
     * OTP Numbers). The primary one is the account's own mobile_number; this
     * table holds the others (App\Support\OtpNumbers). The code goes on
     * WhatsApp to the primary only.
     *
     * The alternative mobile a super admin already had becomes one of them —
     * for the main super admin that is 9368388050, beside the primary
     * 8864985914.
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_otp_numbers')) {
            Schema::create('user_otp_numbers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('phone', 15);
                $table->timestamps();

                $table->unique(['user_id', 'phone']);
            });
        }

        if (!Schema::hasColumn('users', 'alternative_mobile')) {
            return;
        }

        $users = DB::table('users')
            ->whereIn('role', ['super-admin', 'sub-super-admin'])
            ->whereNotNull('alternative_mobile')
            ->get(['id', 'mobile_number', 'alternative_mobile']);

        foreach ($users as $user) {
            $phone = self::tenDigits($user->alternative_mobile);
            if (!$phone || $phone === self::tenDigits($user->mobile_number)) {
                continue;
            }

            DB::table('user_otp_numbers')->insertOrIgnore([
                'user_id'    => $user->id,
                'phone'      => $phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_otp_numbers');
    }

    private static function tenDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }
};
