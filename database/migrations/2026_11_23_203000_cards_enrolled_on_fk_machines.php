<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cards already enrolled on a SecurEye-style machine were kept as raw enroll
     * data (backup number 11, the number as digits). They are now the person's
     * card in biometric_cards, as every card tapped from here on will be. A card
     * or person that already has a row is left as it is.
     */
    public function up(): void
    {
        if (!Schema::hasTable('biometric_templates') || !Schema::hasTable('biometric_cards')) {
            return;
        }

        foreach (DB::table('biometric_templates')->where('fid', 11)->where('template', 'like', 'fk:%')->get() as $t) {
            $digits = rtrim((string) base64_decode(substr($t->template, 3)), "\0");
            if (!ctype_digit($digits) || !preg_match('/^([12])([1-9]\d{0,11})$/', (string) $t->pin, $m)) {
                continue;
            }

            $card = ltrim($digits, '0') ?: '0';
            $type = $m[1] === '2' ? 'teacher' : 'student';
            $id   = (int) $m[2];

            $taken = DB::table('biometric_cards')
                ->where(fn ($q) => $q->where(['organization_id' => $t->organization_id, 'card_number' => $card])
                    ->orWhere(fn ($h) => $h->where(['holder_type' => $type, 'holder_id' => $id])))
                ->exists();
            if ($taken) {
                continue;
            }

            DB::table('biometric_cards')->insert([
                'organization_id' => $t->organization_id,
                'holder_type'     => $type,
                'holder_id'       => $id,
                'card_number'     => $card,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            DB::table('biometric_templates')->where('id', $t->id)->delete();
        }
    }

    public function down(): void
    {
        // The cards stay: they are what the machines hold.
    }
};
