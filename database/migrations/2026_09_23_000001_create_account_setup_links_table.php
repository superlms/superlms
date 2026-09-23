<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The link a new student or teacher is sent on WhatsApp, which opens a page
 * with their login and the app to download.
 *
 * Only a hash of the link's token is kept, never the token, and never the
 * password: the page reads the password from the account when it is opened,
 * and shows it only while it is still the one the account was given
 * (password_hash is the account's hash at the time the link was made).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_setup_links')) {
            return;
        }

        Schema::create('account_setup_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->char('token_hash', 64)->unique();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_setup_links');
    }
};
