<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Message packs (App\Services\MessagePacks): a school has so many
     * WhatsApp, SMS and email messages — given by the Super Admin (paid now,
     * to be paid, or free) or bought by the school (paid, approved by the
     * Super Admin) — and each message it sends takes one (an SMS one a part).
     *
     *   messaging_packs     messages put in a school's account (give) or taken
     *                       back (take), each with its price
     *   messaging_payments  money a school paid for its messages — the
     *                       Ledger's Messaging credits
     *
     * The messaging wallet of 1 Nov 2026 (rupees for every channel) is carried
     * over once: money left in it becomes WhatsApp messages at the WhatsApp
     * rate, free (it was paid already); a wallet below zero becomes what the
     * school owes; a school's Add funds still to approve becomes a pack to
     * approve. Messages are counted against the packs from now on.
     *
     * And sms_messages gets a campaign and a dedupe key, so an SMS notice sent
     * to many goes once to each person.
     */
    public function up(): void
    {
        if (!Schema::hasTable('messaging_packs')) {
            Schema::create('messaging_packs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('channel', 10);                        // whatsapp | sms | email
                $table->string('type', 10)->default('give');          // give | take
                $table->unsignedInteger('messages');
                $table->decimal('rate', 8, 2)->default(0);            // ₹ a message
                $table->decimal('amount', 12, 2)->default(0);         // what the pack costs the school; 0 = free
                $table->string('status', 12)->default('approved');    // pending | approved | rejected
                $table->string('source', 20)->default('super_admin'); // super_admin | school | carried
                $table->string('mode', 40)->nullable();               // how the school paid (its request)
                $table->string('reference', 120)->nullable();
                $table->date('txn_date');
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->string('decision_note')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index(['status', 'txn_date']);
            });
        }

        if (!Schema::hasTable('messaging_payments')) {
            Schema::create('messaging_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('pack_id')->nullable()->index();
                $table->decimal('amount', 12, 2);
                $table->string('mode', 40)->nullable();
                $table->string('reference', 120)->nullable();
                $table->date('paid_on');
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'paid_on']);
                $table->index('paid_on');
            });
        }

        if (Schema::hasTable('sms_messages')) {
            Schema::table('sms_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_messages', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->index();
                }
                if (!Schema::hasColumn('sms_messages', 'dedupe_key')) {
                    $table->string('dedupe_key', 120)->nullable()->unique();
                }
            });
        }

        if (Schema::hasTable('messaging_settings') && !DB::table('messaging_settings')->where('key', 'packs_since')->exists()) {
            $this->carryWallets();
            DB::table('messaging_settings')->insert([
                'key' => 'packs_since', 'value' => now()->toDateTimeString(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** The wallets of 1 Nov 2026 into packs, once. */
    private function carryWallets(): void
    {
        if (!Schema::hasTable('messaging_wallet_transactions')) {
            return;
        }

        $setting = fn (string $key) => DB::table('messaging_settings')->where('key', $key)->value('value');
        $since   = $setting('since') ?: '2026-11-01 00:00:00';
        $rate    = round(max(0.01, (float) ($setting('whatsapp_rate') ?: 0.66)), 2);
        $today   = now()->toDateString();

        $orgIds = DB::table('messaging_wallet_transactions')->distinct()->pluck('organization_id')->map(fn ($id) => (int) $id);
        $usage  = function (string $table) use ($since) {
            return Schema::hasTable($table)
                ? DB::table($table)->whereNotNull('organization_id')->where('billable', true)->where('status', '!=', 'failed')
                    ->where('created_at', '>=', $since)->groupBy('organization_id')
                    ->selectRaw('organization_id, SUM(rate) AS amount')->pluck('amount', 'organization_id')
                : collect();
        };
        $wa = $usage('whatsapp_messages');
        $em = $usage('email_messages');
        $orgIds = $orgIds->merge($wa->keys())->merge($em->keys())->map(fn ($id) => (int) $id)->unique();
        $known  = DB::table('organizations')->whereIn('id', $orgIds->all() ?: [0])->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($known as $orgId) {
            $in  = (float) DB::table('messaging_wallet_transactions')->where('organization_id', $orgId)->where('status', 'approved')->where('type', 'credit')->sum('amount');
            $out = (float) DB::table('messaging_wallet_transactions')->where('organization_id', $orgId)->where('status', 'approved')->where('type', 'debit')->sum('amount');
            $balance = round($in - $out - (float) ($wa[$orgId] ?? 0) - (float) ($em[$orgId] ?? 0), 2);

            if ($balance >= $rate) {
                DB::table('messaging_packs')->insert([
                    'organization_id' => $orgId, 'channel' => 'whatsapp', 'type' => 'give',
                    'messages' => (int) floor($balance / $rate), 'rate' => $rate, 'amount' => 0,
                    'status' => 'approved', 'source' => 'carried', 'txn_date' => $today,
                    'note' => 'The messaging wallet\'s ₹' . number_format($balance, 2) . ', paid before, as WhatsApp messages at ₹' . number_format($rate, 2) . '.',
                    'decided_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            } elseif ($balance <= -0.01) {
                DB::table('messaging_packs')->insert([
                    'organization_id' => $orgId, 'channel' => 'whatsapp', 'type' => 'give',
                    'messages' => 0, 'rate' => $rate, 'amount' => abs($balance),
                    'status' => 'approved', 'source' => 'carried', 'txn_date' => $today,
                    'note' => 'Messages sent before message packs and not paid for yet.',
                    'decided_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // An Add funds still to approve: the same money, as WhatsApp messages to approve.
        DB::table('messaging_wallet_transactions')->where('status', 'pending')->where('type', 'credit')
            ->whereIn('organization_id', $known ?: [0])->orderBy('id')->get()
            ->each(function ($t) use ($rate) {
                DB::table('messaging_packs')->insert([
                    'organization_id' => $t->organization_id, 'channel' => 'whatsapp', 'type' => 'give',
                    'messages' => max(1, (int) floor((float) $t->amount / $rate)), 'rate' => $rate, 'amount' => $t->amount,
                    'status' => 'pending', 'source' => 'school', 'mode' => $t->mode, 'reference' => $t->reference,
                    'txn_date' => $t->txn_date, 'note' => $t->note, 'created_by' => $t->created_by,
                    'created_at' => $t->created_at ?? now(), 'updated_at' => now(),
                ]);
                DB::table('messaging_wallet_transactions')->where('id', $t->id)->update([
                    'status' => 'moved', 'decision_note' => 'Moved to message packs', 'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('messaging_settings')->where('key', 'packs_since')->delete();
        if (Schema::hasTable('sms_messages')) {
            Schema::table('sms_messages', function (Blueprint $table) {
                if (Schema::hasColumn('sms_messages', 'dedupe_key')) {
                    $table->dropUnique(['dedupe_key']);
                    $table->dropColumn('dedupe_key');
                }
                if (Schema::hasColumn('sms_messages', 'campaign_id')) {
                    $table->dropIndex(['campaign_id']);
                    $table->dropColumn('campaign_id');
                }
            });
        }
        Schema::dropIfExists('messaging_payments');
        Schema::dropIfExists('messaging_packs');
    }
};
