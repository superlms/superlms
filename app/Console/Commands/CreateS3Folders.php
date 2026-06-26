<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateS3Folders extends Command
{
    protected $signature   = 'storage:create-s3-folders';
    protected $description = 'Create all required S3 folder structure';

    private array $folders = [
        // SuperAdmin
        'superadmin/app/logos/',
        'superadmin/team-members/',
        'superadmin/social-media/icons/',
        'superadmin/schools/logos/',
        'superadmin/payroll/photos/',
        'superadmin/credit-policies/images/',
        'superadmin/credit-policies/documents/',
        'superadmin/terms-conditions/logos/',
        'superadmin/terms-conditions/files/',

        // Admin - Users
        'admin/students/images/',
        'admin/teachers/images/',
        'admin/account-users/images/',
        'admin/profile/photos/',

        // Admin - Communication
        'admin/announcements/images/',
        'admin/announcements/pdfs/',
        'admin/contact/images/',

        // Admin - Academics
        'admin/content/chapter-images/',
        'admin/content/chapter-pdfs/',
        'admin/content/topic-images/',
        'admin/content/topic-pdfs/',
        'admin/subjects/images/',
        'admin/subjects/detail-images/',
        'admin/syllabus/pdfs/',
        'admin/homework/files/',
        'admin/exam-copies/',
        'admin/rules-regulations/files/',

        // Admin - Library
        'admin/library/covers/',
        'admin/library/pdfs/',

        // Admin - School
        'admin/school-management/photos/',
        'admin/school-documents/',
        'admin/payroll/photos/',

        // Accounts
        'accounts/admissions/result-pdfs/',
        'accounts/admissions/exam-papers/',
        'accounts/contacts/student-images/',
        'accounts/contacts/teacher-images/',
    ];

    public function handle(): int
    {
        $this->info('Creating S3 folder structure...');
        $disk = Storage::disk('s3');
        $created = 0;

        foreach ($this->folders as $folder) {
            try {
                $disk->put($folder, '');
                $this->line("  <fg=green>✓</> $folder");
                $created++;
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> $folder — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done! $created/" . count($this->folders) . " folders created.");
        return 0;
    }
}
