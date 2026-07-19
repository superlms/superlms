<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LmsMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add New Fields For Migration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::table('school_infos', function (Blueprint $table) {
            if (
                !Schema::hasColumn('school_infos', 'usm_mission') || !Schema::hasColumn('school_infos', 'usm_vision') ||
                !Schema::hasColumn('school_infos', 'usm_values') || !Schema::hasColumn('school_infos', 'usm_goals')
            ) {
                $table->text('usm_vision')->nullable();
                $table->text('usm_mission')->nullable();
                $table->text('usm_values')->nullable();
                $table->text('usm_goals')->nullable();
                $this->info('adding [usm] fields in [school_infos] table');
            }
        });

        Schema::table('school_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('school_infos', 'school_document_text')) {
                $table->text('school_document_text')->nullable();
                $this->info('adding [school_document_text] field in [school_infos] table');
            }
        });

        Schema::table('school_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('school_infos', 'custom_sections')) {
                $table->json('custom_sections')->nullable();
                $this->info('adding [custom_sections] field in [school_infos] table');
            }
        });

        Schema::table('student_details', function (Blueprint $table) {
            if (!Schema::hasColumn('student_details', 'transportation_required')) {
                $table->boolean('transportation_required')->default(false);
                $this->info('adding [transportation_required] field in [student_details] table');
            }
        });

        Schema::table('student_details', function (Blueprint $table) {
            if (!Schema::hasColumn('student_details', 'organization_id')) {
                $table->foreignIdFor(Organization::class)->default(0);
                $this->info('adding [organization_id] field in [student_details] table');
            }
        });

        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'content_type')) {
                $table->string('content_type')->nullable()->after('description');
                $table->string('file_path')->nullable()->after('content_type');
                $table->string('thumbnail')->nullable()->after('file_path');
                $table->integer('duration')->nullable()->after('thumbnail');
                $table->integer('order')->default(0)->after('duration');
                $table->boolean('is_published')->default(false)->after('order');
                $table->boolean('is_free')->default(false)->after('is_published');
                $table->json('metadata')->nullable()->after('is_free');
                $table->softDeletes();

                $table->index(['organization_id', 'is_published']);
                $table->index(['subject_id', 'is_published']);
                $table->index(['content_type', 'is_published']);

                $this->info('adding content management fields in [chapters] table');
            }
        });

        // Chapter image_path / pdf_path were referenced in code + model fillable
        // but never added by any migration. Add them here so /1/content stops
        // 500'ing with "Unknown column 'image_path'".
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'image_path')) {
                $table->string('image_path')->nullable()->after('file_path');
                $this->info('adding [image_path] field in [chapters] table');
            }
            if (!Schema::hasColumn('chapters', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('image_path');
                $this->info('adding [pdf_path] field in [chapters] table');
            }
        });

        Schema::table('teacher_arrangements', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_arrangements', 'organization_id')) {
                $table->foreignIdFor(Organization::class)->default(0);
                $this->info('adding [organization_id] field in [teacher_arrangements] table');
            }
        });

        Schema::table('teacher_arrangements', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_arrangements', 'arranged_by')) {
                $table->unsignedBigInteger('arranged_by')->default(0);
                $this->info('adding [arranged_by] field in [teacher_arrangements] table');
            }
        });

        Schema::table('teacher_time_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_time_tables', 'is_active')) {
                $table->boolean('is_active')->default(false);
                $this->info('adding [is_active] field in [teacher_time_tables] table');
            }
        });

        Schema::table('exam_copies', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_copies', 'file')) {
                $table->string('file')->default(0);
                $this->info('adding [file] field in [exam_copies] table');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'image')) {
                $table->string('image')->default(0);
                $this->info('adding [image] field in [subjects] table');
            }
        });

        Schema::table('exam_copies', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_copies', 'pdf_path')) {
                $table->string('pdf_path')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
                $this->info('adding [pdf_path] field in [exam_copies] table');
            }
        });


        Schema::table('home_works', function (Blueprint $table) {
            if (!Schema::hasColumn('home_works', 'file')) {
                $table->string('file')->nullable();
                $this->info('adding [file] field in [file] table');
            }
        });

        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections', 'organization_id')) {
                $table->foreignIdFor(Organization::class)->default(0);
                $this->info('adding [organization_id] field in [sections] table');
            }
        });

        Schema::table('website_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('website_contacts', 'subject')) {
                $table->string('subject')->nullable()->after('school_name');
                $table->text('remark')->nullable()->after('description');
                $this->info('adding [subject] field in [website_contacts] table');
            }
        });

        Schema::table('student_details', function (Blueprint $table) {
            if (!Schema::hasColumn('student_details', 'appar_id')) {
                $table->string('appar_id')->nullable();
                $table->string('registration_number')->nullable();
                $this->info('adding [appar_id] field in [student_details] table');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dob')) {
                $table->date('dob')->nullable();
                $this->info('adding [dob] field in [users] table');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable();
                $this->info('adding [gender] field in [users] table');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'affiliation_no')) {
                $table->string('affiliation_no')->nullable()->after('school_code');
                $table->string('udise_number')->nullable()->after('affiliation_no');
                $table->string('bank_name')->nullable()->after('address');
                $table->string('bank_account_no')->nullable()->after('bank_name');
                $table->string('bank_ifsc')->nullable()->after('bank_account_no');
                $table->string('bank_branch')->nullable()->after('bank_ifsc');
                $table->string('bank_holder_name')->nullable()->after('bank_branch');
                $this->info('adding [affiliation_no] field in [organizations] table');
            }
        });

        Schema::table('school_users', function (Blueprint $table) {
            if (!Schema::hasColumn('school_users', 'alternate_mobile_number')) {
                $table->string('image')->nullable();
                $table->string('alternate_mobile_number')->nullable();
                $this->info('adding [image] field in [school_users] table');
            }
        });

        Schema::table('contact_super_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_super_admins', 'super_admin_attachment')) {
                $table->string('super_admin_attachment')->nullable()->after('super_admin_text');
                $this->info('adding [super_admin_attachment] field in [contact_super_admins] table');
            }
        });

        // Ensure role_user pivot table exists (not covered by a regular migration)
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
                $table->primary(['role_id', 'user_id']);
            });
            $this->info('created [role_user] pivot table');
        }

        // Ensure super-admin role exists
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Administrator with All access']
        );

        // Seed super admin accounts (idempotent — firstOrCreate only sets the
        // password on first create, so a later UI password change is NOT reset
        // on the next deploy). email => initial password.
        $superAdmins = [
            'superlms.india@gmail.com'   => 'Super2026#@lms',
            'superlmsofficial@gmail.com' => 'Super2026#@lms',
        ];

        foreach ($superAdmins as $email => $plainPassword) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make($plainPassword),
                    'role' => 'super-admin',
                    'is_active' => 1,
                    'organization_id' => 0,
                ]
            );
            $user->roles()->syncWithoutDetaching($superAdminRole->id);
            $this->info("Super admin ready: {$email}");
        }

        // Enforce: ONLY the emails above may be super-admin. Remove any other
        // super-admin accounts (e.g. old seeded ones) so they lose access.
        $allowed = array_keys($superAdmins);
        $stale = User::where('role', 'super-admin')->whereNotIn('email', $allowed)->get();
        foreach ($stale as $u) {
            $u->roles()->detach();
            $u->delete();
            $this->warn("Removed stale super-admin: {$u->email}");
        }
    }
}
