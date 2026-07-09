<?php

namespace App\Providers;

use App\Models\AboutApp;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\RateLms;
use App\Models\Admin\TermAndCondition;
use App\Models\Blog;
use App\Models\CareerApplication;
use App\Models\ExecutiveApplication;
use App\Models\Faq;
use App\Models\Organization;
use App\Models\PrivacyPolicy;
use App\Models\SuperAdmin\CreditPolicy;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use App\Models\TermOfUse;
use App\Models\User;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Services\ActivityNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /** Bookkeeping columns that never count as a "real" change. */
    private const IGNORED_CHANGES = [
        'created_at', 'updated_at', 'last_login_at', 'remember_token',
        'password', 'password_plain', 'fcm_token', 'read_at',
    ];

    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton('firebase', function () {
            $factory = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->withDatabaseUri(config('services.firebase.database_url'));

            return $factory;
        });
    }

    /**
     * Bootstrap services.
     *
     * Super-admin activity notifications: whenever any of these records is
     * created or meaningfully updated (from any panel or the public website),
     * push a detailed notification to all super-admin users — stored in the
     * header bell and delivered via FCM web push.
     */
    public function boot(): void
    {
        $this->contentPageNotifications();
        $this->faqAndBlogNotifications();
        $this->websiteQueryNotifications();
        $this->supportAndRatingNotifications();
        $this->creditNotifications();
        $this->panelUserNotifications();
        $this->payrollNotifications();
        $this->schoolNotifications();
        $this->schoolFeeNotifications();
    }

    // ─── About App / Terms & Conditions / Privacy Policy / Terms of Use ───────

    private function contentPageNotifications(): void
    {
        $pages = [
            [AboutApp::class,         'About App',          'about_app'],
            [PrivacyPolicy::class,    'Privacy Policy',     'privacy_policy'],
            [TermAndCondition::class, 'Terms & Conditions', 'terms_condition'],
            [TermOfUse::class,        'Terms of Use',       'terms_of_use'],
        ];

        foreach ($pages as [$class, $label, $type]) {
            $class::created(fn($m) => ActivityNotifier::toSuperAdmins(
                "{$label} added",
                "The {$label} content has been added.",
                ['type' => $type, 'id' => $m->id]
            ));

            $class::updated(function ($m) use ($label, $type) {
                if (!$changed = $this->changedSummary($m)) {
                    return;
                }
                ActivityNotifier::toSuperAdmins(
                    "{$label} updated",
                    "The {$label} content has been updated (changed: {$changed}).",
                    ['type' => $type, 'id' => $m->id]
                );
            });
        }
    }

    // ─── FAQs & Blogs ──────────────────────────────────────────────────────────

    private function faqAndBlogNotifications(): void
    {
        Faq::created(fn($faq) => ActivityNotifier::toSuperAdmins(
            'New FAQ added',
            "FAQ added under \"{$faq->category}\": " . Str::limit((string) $faq->question, 120),
            ['type' => 'faq', 'id' => $faq->id]
        ));

        Blog::created(fn($blog) => ActivityNotifier::toSuperAdmins(
            'New blog added',
            "Blog \"{$blog->title}\"" . ($blog->category ? " ({$blog->category})" : '') . ' has been added.',
            ['type' => 'blog', 'id' => $blog->id]
        ));
    }

    // ─── Website queries: demo / contact / executive / careers ────────────────

    private function websiteQueryNotifications(): void
    {
        WebsiteDemo::created(fn($demo) => ActivityNotifier::toSuperAdmins(
            'New demo enquiry',
            "Demo enquiry from {$demo->full_name}"
                . ($demo->school_name ? " ({$demo->school_name})" : '')
                . ($demo->phone ? " — {$demo->phone}" : '')
                . ($demo->city ? ", {$demo->city}" : '') . '.',
            ['type' => 'enquiry', 'id' => $demo->id]
        ));

        WebsiteDemo::updated(function ($demo) {
            if (!$changed = $this->changedSummary($demo)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Demo enquiry updated',
                "Demo enquiry of {$demo->full_name} updated (changed: {$changed})"
                    . ($demo->remark ? " — remark: " . Str::limit((string) $demo->remark, 100) : '') . '.',
                ['type' => 'enquiry', 'id' => $demo->id]
            );
        });

        WebsiteContact::created(fn($contact) => ActivityNotifier::toSuperAdmins(
            'New contact enquiry',
            "Enquiry from {$contact->full_name}"
                . ($contact->school_name ? " ({$contact->school_name})" : '')
                . ($contact->subject ? " — \"" . Str::limit((string) $contact->subject, 80) . '"' : '') . '.',
            ['type' => 'enquiry', 'id' => $contact->id]
        ));

        WebsiteContact::updated(function ($contact) {
            if (!$changed = $this->changedSummary($contact)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Contact enquiry updated',
                "Enquiry of {$contact->full_name} updated (changed: {$changed})"
                    . ($contact->remark ? " — remark: " . Str::limit((string) $contact->remark, 100) : '') . '.',
                ['type' => 'enquiry', 'id' => $contact->id]
            );
        });

        ExecutiveApplication::created(fn($app) => ActivityNotifier::toSuperAdmins(
            'New executive application',
            "Become-an-executive query from {$app->full_name}"
                . ($app->mobile ? " — {$app->mobile}" : '')
                . ($app->qualification ? " ({$app->qualification})" : '') . '.',
            ['type' => 'executive', 'id' => $app->id]
        ));

        ExecutiveApplication::updated(function ($app) {
            if (!$changed = $this->changedSummary($app)) {
                return;
            }
            $detail = [];
            if ($app->wasChanged('status')) {
                $detail[] = 'status: ' . Str::headline((string) $app->status);
            }
            if ($app->wasChanged('admin_remark') && $app->admin_remark) {
                $detail[] = 'remark: ' . Str::limit((string) $app->admin_remark, 100);
            }
            ActivityNotifier::toSuperAdmins(
                'Executive application updated',
                "Application of {$app->full_name} updated"
                    . ($detail ? ' — ' . implode(' · ', $detail) : " (changed: {$changed})") . '.',
                ['type' => 'executive', 'id' => $app->id]
            );
        });

        CareerApplication::created(fn($app) => ActivityNotifier::toSuperAdmins(
            'New job application',
            "Career application from {$app->full_name} for \"{$app->job_role}\""
                . ($app->mobile ? " — {$app->mobile}" : '')
                . ($app->experience ? " ({$app->experience} experience)" : '') . '.',
            ['type' => 'career', 'id' => $app->id]
        ));
    }

    // ─── Support queries & school ratings ──────────────────────────────────────

    private function supportAndRatingNotifications(): void
    {
        ContactSuperAdmin::created(fn($query) => ActivityNotifier::toSuperAdmins(
            'New support query',
            'Support query from ' . $this->orgName($query)
                . ($query->topic ? " — \"" . Str::limit((string) $query->topic, 80) . '"' : '')
                . ($query->admin_query ? ': ' . Str::limit((string) $query->admin_query, 100) : '') . '.',
            ['type' => 'support', 'id' => $query->id]
        ));

        ContactSuperAdmin::updated(function ($query) {
            if (!$changed = $this->changedSummary($query)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Support query updated',
                'Support query of ' . $this->orgName($query)
                    . ($query->topic ? " (\"" . Str::limit((string) $query->topic, 80) . '")' : '')
                    . " updated (changed: {$changed}).",
                ['type' => 'support', 'id' => $query->id]
            );
        });

        RateLms::created(fn($rating) => ActivityNotifier::toSuperAdmins(
            'New school rating',
            $this->orgName($rating) . " rated the platform {$rating->rating}/5"
                . ($rating->feedback ? ' — "' . Str::limit((string) $rating->feedback, 120) . '"' : '') . '.',
            ['type' => 'rating', 'id' => $rating->id]
        ));
    }

    // ─── Credit queries & policies ─────────────────────────────────────────────

    private function creditNotifications(): void
    {
        CreditQuery::created(fn($credit) => ActivityNotifier::toSuperAdmins(
            'New credit request',
            $this->orgName($credit) . ' requested credit'
                . ($credit->amount ? ' of ₹' . number_format((float) $credit->amount, 2) : '')
                . ($credit->heading ? " — \"" . Str::limit((string) $credit->heading, 80) . '"' : '') . '.',
            ['type' => 'credit', 'id' => $credit->id]
        ));

        CreditQuery::updated(function ($credit) {
            if (!$changed = $this->changedSummary($credit)) {
                return;
            }
            $detail = [];
            if ($credit->wasChanged('status')) {
                $detail[] = 'status: ' . Str::headline((string) $credit->status);
            }
            if ($credit->wasChanged('collected_at') && $credit->collected_at) {
                $detail[] = 'payment collected';
            }
            if ($credit->wasChanged('admin_remark') && $credit->admin_remark) {
                $detail[] = 'remark: ' . Str::limit((string) $credit->admin_remark, 80);
            }
            ActivityNotifier::toSuperAdmins(
                'Credit request updated',
                'Credit request of ' . $this->orgName($credit)
                    . ($credit->amount ? ' (₹' . number_format((float) $credit->amount, 2) . ')' : '')
                    . ($detail ? ' — ' . implode(' · ', $detail) : " updated (changed: {$changed})") . '.',
                ['type' => 'credit', 'id' => $credit->id]
            );
        });

        CreditPolicy::created(fn($policy) => ActivityNotifier::toSuperAdmins(
            'Credit policy added',
            "Credit policy \"" . Str::limit((string) $policy->title, 100) . '" has been added.',
            ['type' => 'credit_policy', 'id' => $policy->id]
        ));

        CreditPolicy::updated(function ($policy) {
            if (!$changed = $this->changedSummary($policy)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Credit policy updated',
                "Credit policy \"" . Str::limit((string) $policy->title, 100) . "\" updated (changed: {$changed}).",
                ['type' => 'credit_policy', 'id' => $policy->id]
            );
        });
    }

    // ─── Panel users (sub-super-admins) ────────────────────────────────────────

    private function panelUserNotifications(): void
    {
        User::created(function ($user) {
            if ($user->role !== 'sub-super-admin') {
                return;
            }
            $permCount = count((array) $user->permissions);
            ActivityNotifier::toSuperAdmins(
                'User added',
                "User {$user->name} ({$user->email}) has been added with {$permCount} permission(s).",
                ['type' => 'panel_user', 'id' => $user->id]
            );
        });

        User::updated(function ($user) {
            if ($user->role !== 'sub-super-admin') {
                return;
            }
            if (!$changed = $this->changedSummary($user)) {
                return;
            }
            $extra = $user->wasChanged('permissions')
                ? ' Permissions now: ' . count((array) $user->permissions) . '.'
                : '';
            ActivityNotifier::toSuperAdmins(
                'User updated',
                "User {$user->name} ({$user->email}) updated (changed: {$changed}).{$extra}",
                ['type' => 'panel_user', 'id' => $user->id]
            );
        });
    }

    // ─── Payroll: employees, salary payments ───────────────────────────────────
    //     (Attendance is announced as a single summary from the Payroll screen —
    //      a per-row hook would flood the bell when a full day is submitted.)

    private function payrollNotifications(): void
    {
        SuperAdminEmployee::created(fn($emp) => ActivityNotifier::toSuperAdmins(
            'Employee added',
            "Employee {$emp->name}"
                . ($emp->designation ? " ({$emp->designation})" : '')
                . ' added to payroll'
                . ($emp->salary ? ' — salary ₹' . number_format((float) $emp->salary, 2) : '') . '.',
            ['type' => 'payroll_employee', 'id' => $emp->id]
        ));

        SuperAdminEmployee::updated(function ($emp) {
            if (!$changed = $this->changedSummary($emp)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Employee updated',
                "Employee {$emp->name}'s details updated (changed: {$changed}).",
                ['type' => 'payroll_employee', 'id' => $emp->id]
            );
        });

        $salaryBody = function ($pay, string $word) {
            $name = $pay->employee?->name ?? 'an employee';
            return "Salary {$word} for {$name} — ₹" . number_format((float) $pay->amount, 2)
                . " for {$pay->month}"
                . ($pay->payment_mode ? ' via ' . Str::headline((string) $pay->payment_mode) : '')
                . ($pay->receipt_number ? " (receipt {$pay->receipt_number})" : '') . '.';
        };

        SuperAdminSalaryPayment::created(fn($pay) => ActivityNotifier::toSuperAdmins(
            'Salary paid', $salaryBody($pay, 'paid'),
            ['type' => 'payroll_salary', 'id' => $pay->id]
        ));

        SuperAdminSalaryPayment::updated(function ($pay) use ($salaryBody) {
            if (!$this->changedSummary($pay)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'Salary payment updated', $salaryBody($pay, 'payment updated'),
                ['type' => 'payroll_salary', 'id' => $pay->id]
            );
        });
    }

    // ─── Schools (organizations) ───────────────────────────────────────────────

    private function schoolNotifications(): void
    {
        Organization::created(fn($org) => ActivityNotifier::toSuperAdmins(
            'New school added',
            "School \"{$org->name}\" has been added"
                . ($org->email ? " — {$org->email}" : '')
                . ($org->mobile_number ? ", {$org->mobile_number}" : '')
                . ($org->state ? " ({$org->state})" : '') . '.',
            ['type' => 'school', 'id' => $org->id]
        ));

        Organization::updated(function ($org) {
            $changed = array_diff(array_keys($org->getChanges()), self::IGNORED_CHANGES);
            if (empty($changed)) {
                return;
            }

            $bankFields = ['bank_name', 'bank_account_no', 'bank_ifsc', 'bank_branch', 'bank_holder_name'];
            $bankChanged = array_intersect($changed, $bankFields);

            if (!empty($bankChanged)) {
                ActivityNotifier::toSuperAdmins(
                    'School bank details updated',
                    "Bank details of \"{$org->name}\" updated"
                        . " (changed: " . $this->headlineList($bankChanged) . ')'
                        . ($org->bank_name ? " — {$org->bank_name}" : '')
                        . ($org->bank_account_no ? ', A/C ' . $org->bank_account_no : '') . '.',
                    ['type' => 'school_bank', 'id' => $org->id]
                );
            }

            $otherChanged = array_diff($changed, $bankFields);
            if (!empty($otherChanged)) {
                ActivityNotifier::toSuperAdmins(
                    'School details updated',
                    "Details of \"{$org->name}\" updated (changed: " . $this->headlineList($otherChanged) . ').',
                    ['type' => 'school', 'id' => $org->id]
                );
            }
        });
    }

    // ─── School fee structures & fee payments (Super Admin Fees) ──────────────

    private function schoolFeeNotifications(): void
    {
        SuperAdminFeeStructure::created(fn($fee) => ActivityNotifier::toSuperAdmins(
            'School fee structure added',
            'Fee structure added for ' . $this->orgName($fee)
                . ($fee->fee_label ? " — \"{$fee->fee_label}\"" : '')
                . ' (' . Str::headline((string) $fee->fee_type) . ')'
                . ', ₹' . number_format((float) ($fee->total_amount ?? $fee->amount), 2)
                . ($fee->academic_year ? " for {$fee->academic_year}" : '') . '.',
            ['type' => 'school_fee_structure', 'id' => $fee->id]
        ));

        SuperAdminFeeStructure::updated(function ($fee) {
            if (!$changed = $this->changedSummary($fee)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'School fee updated',
                'Fee structure of ' . $this->orgName($fee)
                    . ($fee->fee_label ? " (\"{$fee->fee_label}\")" : '')
                    . ' updated — now ₹' . number_format((float) ($fee->total_amount ?? $fee->amount), 2)
                    . " (changed: {$changed}).",
                ['type' => 'school_fee_structure', 'id' => $fee->id]
            );
        });

        SuperAdminFeePayment::created(fn($pay) => ActivityNotifier::toSuperAdmins(
            'School fee payment recorded',
            'Fee payment of ₹' . number_format((float) $pay->amount, 2)
                . ' recorded for ' . $this->orgName($pay)
                . ($pay->receipt_number ? " (receipt {$pay->receipt_number})" : '')
                . ($pay->academic_year ? " — {$pay->academic_year}" : '') . '.',
            ['type' => 'school_fee_payment', 'id' => $pay->id]
        ));

        SuperAdminFeePayment::updated(function ($pay) {
            if (!$changed = $this->changedSummary($pay)) {
                return;
            }
            ActivityNotifier::toSuperAdmins(
                'School fee payment updated',
                'Fee payment of ' . $this->orgName($pay)
                    . ' updated — now ₹' . number_format((float) $pay->amount, 2)
                    . " (changed: {$changed}).",
                ['type' => 'school_fee_payment', 'id' => $pay->id]
            );
        });
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /** Human list of meaningfully-changed columns, '' if only bookkeeping changed. */
    private function changedSummary(Model $model): string
    {
        $changed = array_diff(array_keys($model->getChanges()), self::IGNORED_CHANGES);

        return $this->headlineList($changed);
    }

    /** "bank_name, bank_ifsc" → "Bank Name, Bank Ifsc" */
    private function headlineList(array $columns): string
    {
        return implode(', ', array_map(fn($c) => Str::headline($c), array_values($columns)));
    }

    /** The related school's name, for models with an organization() relation. */
    private function orgName(Model $model): string
    {
        try {
            return $model->organization?->name ?: 'a school';
        } catch (\Throwable $e) {
            return 'a school';
        }
    }
}
