<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Messaging (App\Services\MessagingWallet):
     *
     *   messaging_wallet_transactions  money put in a school's wallet (or taken
     *                                  out) — by the Super Admin, or asked for by
     *                                  the school and approved
     *   messaging_settings             the rates, prepaid on / off, the payment
     *                                  details schools see, and since when
     *                                  messages are paid from the wallet
     *   messaging_school_settings      a school's email messaging on / off
     *   messaging_campaigns            a message a school sent to many (WhatsApp
     *                                  or email), with how far it got
     *   email_messages                 every email a school sent, with its rate
     */
    public function up(): void
    {
        if (!Schema::hasTable('messaging_wallet_transactions')) {
            Schema::create('messaging_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('type', 10);                          // credit | debit
                $table->decimal('amount', 12, 2);
                $table->string('status', 12)->default('approved');   // pending | approved | rejected
                $table->string('source', 20)->default('super_admin'); // super_admin | school
                $table->string('mode', 40)->nullable();
                $table->string('reference', 120)->nullable();
                $table->text('note')->nullable();
                // Money the platform received — a credit to the Super Admin's Ledger.
                $table->boolean('in_ledger')->default(false);
                $table->date('txn_date');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->string('decision_note')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index(['status', 'txn_date']);
            });
        }

        if (!Schema::hasTable('messaging_settings')) {
            Schema::create('messaging_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // Messages are paid from the wallet from today; what went before is not.
            DB::table('messaging_settings')->insert([
                ['key' => 'since', 'value' => now()->toDateTimeString(), 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (!Schema::hasTable('messaging_school_settings')) {
            Schema::create('messaging_school_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->unique()->constrained('organizations')->cascadeOnDelete();
                $table->boolean('email_enabled')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('messaging_campaigns')) {
            Schema::create('messaging_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('channel', 10);                       // whatsapp | email
                $table->string('audience', 20);                      // students | teachers | everyone
                $table->unsignedBigInteger('standard_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->string('audience_label')->nullable();
                $table->string('subject')->nullable();
                $table->text('body');
                $table->unsignedInteger('recipients')->default(0);
                $table->unsignedInteger('done')->default(0);         // recipients gone through
                $table->unsignedInteger('sent')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('skipped')->default(0);      // no number / email, or the wallet ran out
                $table->decimal('rate', 8, 2)->default(0);
                $table->string('status', 12)->default('queued');     // queued | sending | done
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('email_messages')) {
            Schema::create('email_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('student_detail_id')->nullable();
                $table->string('to_email');
                $table->string('subject');
                $table->string('dedupe_key', 120)->nullable()->unique();
                $table->string('status', 12)->default('sending');    // sending | sent | failed
                $table->text('error')->nullable();
                $table->boolean('billable')->default(true);
                $table->decimal('rate', 8, 2)->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('messaging_campaigns');
        Schema::dropIfExists('messaging_school_settings');
        Schema::dropIfExists('messaging_settings');
        Schema::dropIfExists('messaging_wallet_transactions');
    }
};
