<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Super Admin → Schools → Delete takes all of the school out of the database:
 * every table with its organization_id, as before, and now also the tables
 * that reach it only through its people or records — announcement reads,
 * homework completions, chat blocks, datesheet papers, role_user, sessions —
 * while another school's rows stay.
 */
class SchoolDeletePurgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('school_code')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('home_works', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('announcements', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('exam_datesheets', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('chat_conversations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('conversation_id'); $t->unsignedBigInteger('sender_id'); $t->timestamps();
        });

        // Tables without organization_id, reached only through a parent.
        Schema::create('home_work_completions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('home_work_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('user_id'); $t->timestamps();
        });
        Schema::create('announcement_reads', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('announcement_id'); $t->unsignedBigInteger('user_id'); $t->timestamps();
        });
        Schema::create('exam_datesheet_papers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('exam_datesheet_id'); $t->timestamps();
        });
        Schema::create('chat_blocks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('blocked_user_id'); $t->timestamps();
        });
        Schema::create('chat_message_deletes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('message_id'); $t->unsignedBigInteger('user_id'); $t->timestamps();
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('user_id');
        });
        Schema::create('student_admission_dates_removed', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->date('date_of_admission')->nullable();
        });
        // The Super Admin's own: never touched.
        Schema::create('super_admin_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable();
        });

        // Two schools, the same shape each: 1 is deleted, 2 stays.
        foreach ([1, 2] as $o) {
            $base = $o * 100;
            DB::table('organizations')->insert(['id' => $o, 'name' => "School $o"]);
            DB::table('users')->insert([['id' => $base + 1, 'name' => 'S', 'organization_id' => $o], ['id' => $base + 2, 'name' => 'T', 'organization_id' => $o]]);
            DB::table('student_details')->insert(['id' => $base + 1, 'user_id' => $base + 1, 'organization_id' => $o]);
            DB::table('teacher_details')->insert(['id' => $base + 1, 'user_id' => $base + 2, 'organization_id' => $o]);
            DB::table('home_works')->insert(['id' => $base + 1, 'organization_id' => $o]);
            DB::table('announcements')->insert(['id' => $base + 1, 'organization_id' => $o]);
            DB::table('exam_datesheets')->insert(['id' => $base + 1, 'organization_id' => $o]);
            DB::table('chat_conversations')->insert(['id' => $base + 1, 'organization_id' => $o]);
            DB::table('chat_messages')->insert(['id' => $base + 1, 'conversation_id' => $base + 1, 'sender_id' => $base + 2]);

            DB::table('home_work_completions')->insert(['home_work_id' => $base + 1, 'student_detail_id' => $base + 1, 'user_id' => $base + 1]);
            DB::table('announcement_reads')->insert(['announcement_id' => $base + 1, 'user_id' => $base + 1]);
            DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $base + 1]);
            DB::table('chat_blocks')->insert(['user_id' => $base + 1, 'blocked_user_id' => $base + 2]);
            DB::table('chat_message_deletes')->insert(['message_id' => $base + 1, 'user_id' => $base + 1]);
            DB::table('role_user')->insert(['role_id' => 1, 'user_id' => $base + 2]);
            DB::table('student_admission_dates_removed')->insert(['student_detail_id' => $base + 1]);
            DB::table('super_admin_attendances')->insert(['user_id' => $base + 1]);
        }
    }

    public function test_deleting_a_school_takes_all_its_rows_and_leaves_the_other_school(): void
    {
        Organization::find(1)->delete();

        // Its own rows (organization_id, or the conversation's messages): only school 2's are left.
        $this->assertSame([201, 202], DB::table('users')->pluck('id')->all());
        foreach (['student_details', 'teacher_details', 'home_works', 'announcements', 'exam_datesheets', 'chat_conversations', 'chat_messages'] as $t) {
            $this->assertSame([201], DB::table($t)->pluck('id')->all(), $t);
        }

        // Rows reached through a parent: only school 2's are left.
        $this->assertSame([201], DB::table('home_work_completions')->pluck('student_detail_id')->all());
        $this->assertSame([201], DB::table('announcement_reads')->pluck('announcement_id')->all());
        $this->assertSame([201], DB::table('exam_datesheet_papers')->pluck('exam_datesheet_id')->all());
        $this->assertSame([201], DB::table('chat_blocks')->pluck('user_id')->all());
        $this->assertSame([201], DB::table('chat_message_deletes')->pluck('message_id')->all());
        $this->assertSame([202], DB::table('role_user')->pluck('user_id')->all());
        $this->assertSame([201], DB::table('student_admission_dates_removed')->pluck('student_detail_id')->all());

        // The school itself is gone; the Super Admin's own table untouched.
        $this->assertSame([2], DB::table('organizations')->pluck('id')->all());
        $this->assertSame(2, DB::table('super_admin_attendances')->count());
    }
}
