<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biometric attendance (App\Support\Biometric): a school the super admin
     * turns it on for has its attendance machines (fingerprint and 125 kHz
     * card) send every punch here (ADMS push, App\Http\Controllers\BiometricPushController),
     * and each punch marks the day:
     *   - a student punching by the school's last time is Present; later, Absent;
     *   - a teacher's first punch is their punch-in, the last their punch-out.
     *
     * Kept apart from the attendance tables, which only gain the time of the
     * punch (and that it came from a machine); a school without it reads
     * exactly what it read before.
     */
    public function up(): void
    {
        if (!Schema::hasTable('biometric_settings')) {
            Schema::create('biometric_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->unique()->constrained('organizations')->cascadeOnDelete();
                $table->boolean('enabled')->default(false);
                $table->time('student_last_time')->nullable();   // a later punch is Absent
                $table->time('teacher_in_time')->nullable();     // punch-in after it is late
                $table->time('teacher_out_time')->nullable();    // punch-out before it is early
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // The school's machines, known by the serial number each one sends.
        if (!Schema::hasTable('biometric_devices')) {
            Schema::create('biometric_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('serial_number', 64)->unique();
                $table->string('name')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->string('last_ip', 64)->nullable();
                $table->string('attlog_stamp', 32)->nullable();
                $table->string('operlog_stamp', 32)->nullable();
                $table->timestamps();
            });
        }

        // What a machine is to do next (add a user, a card, a fingerprint), handed over when it asks.
        if (!Schema::hasTable('biometric_commands')) {
            Schema::create('biometric_commands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biometric_device_id')->constrained('biometric_devices')->cascadeOnDelete();
                $table->text('command');
                $table->string('status', 10)->default('pending'); // pending | sent | done | failed
                $table->integer('return_code')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('done_at')->nullable();
                $table->timestamps();
                $table->index(['biometric_device_id', 'status']);
            });
        }

        // The 125 kHz card each student or teacher carries: one card, one person.
        if (!Schema::hasTable('biometric_cards')) {
            Schema::create('biometric_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('holder_type', 10);                  // student | teacher
                $table->unsignedBigInteger('holder_id');            // student_details.id | teacher_details.id
                $table->string('card_number', 32);
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();
                $table->unique(['organization_id', 'card_number']);
                $table->unique(['holder_type', 'holder_id']);
            });
        }

        // Every punch as the machine sent it, and what it did to the day.
        if (!Schema::hasTable('biometric_punches')) {
            Schema::create('biometric_punches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('biometric_device_id')->nullable();
                $table->string('pin', 20);
                $table->string('holder_type', 10)->nullable();
                $table->unsignedBigInteger('holder_id')->nullable();
                $table->dateTime('punched_at');
                $table->string('verify', 16)->nullable();          // fingerprint | card | face | password | other
                $table->string('result', 24)->nullable();          // present | late | in | out | repeat | unknown | off | holiday
                $table->timestamps();
                $table->unique(['biometric_device_id', 'pin', 'punched_at'], 'biometric_punches_once');
                $table->index(['organization_id', 'punched_at']);
                $table->index(['holder_type', 'holder_id', 'punched_at']);
            });
        }

        // Fingerprints enrolled on one machine, kept to put on the school's others.
        if (!Schema::hasTable('biometric_templates')) {
            Schema::create('biometric_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->string('pin', 20);
                $table->unsignedSmallInteger('fid')->default(0);
                $table->unsignedInteger('size')->default(0);
                $table->unsignedTinyInteger('valid')->default(1);
                $table->longText('template');
                $table->unsignedBigInteger('from_device_id')->nullable();
                $table->timestamps();
                $table->unique(['organization_id', 'pin', 'fid']);
            });
        }

        // The time of the punch on the day it marked.
        if (Schema::hasTable('student_attendances')) {
            Schema::table('student_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('student_attendances', 'punch_time')) {
                    $table->time('punch_time')->nullable();
                }
                if (!Schema::hasColumn('student_attendances', 'source')) {
                    $table->string('source', 16)->nullable();      // null = marked by hand, biometric = a machine
                }
            });
        }
        if (Schema::hasTable('teacher_attendances')) {
            Schema::table('teacher_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_attendances', 'punch_in')) {
                    $table->time('punch_in')->nullable();
                }
                if (!Schema::hasColumn('teacher_attendances', 'punch_out')) {
                    $table->time('punch_out')->nullable();
                }
                if (!Schema::hasColumn('teacher_attendances', 'source')) {
                    $table->string('source', 16)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['teacher_attendances' => ['punch_in', 'punch_out', 'source'], 'student_attendances' => ['punch_time', 'source']] as $t => $cols) {
            if (Schema::hasTable($t)) {
                $have = array_values(array_filter($cols, fn ($c) => Schema::hasColumn($t, $c)));
                if ($have) {
                    Schema::table($t, fn (Blueprint $table) => $table->dropColumn($have));
                }
            }
        }
        Schema::dropIfExists('biometric_templates');
        Schema::dropIfExists('biometric_punches');
        Schema::dropIfExists('biometric_cards');
        Schema::dropIfExists('biometric_commands');
        Schema::dropIfExists('biometric_devices');
        Schema::dropIfExists('biometric_settings');
    }
};
