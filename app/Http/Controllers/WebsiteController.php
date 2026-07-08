<?php

namespace App\Http\Controllers;

use App\Models\Admin\RateLms;
use App\Models\Admin\TermAndCondition;
use App\Models\Organization;
use App\Models\PrivacyPolicy;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\TermOfUse;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Models\WebsitePage;
use App\Models\SchoolWebsiteEnquiry;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    /** GET /api/website/stats */
    public function stats()
    {
        $schools  = Organization::count();
        $students = StudentDetail::count();
        $teachers = TeacherDetail::count();
        $rating   = RateLms::where('status', 1)->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                'schools'  => $schools,
                'students' => $students,
                'teachers' => $teachers,
                'rating'   => $rating ? round($rating, 1) : 4.9,
            ],
        ]);
    }

    /** GET /api/website/schools */
    public function schools()
    {
        $schools = Organization::where('status', true)
            ->select('name', 'logo')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($school) => [
                'name'     => $school->name,
                'logo_url' => $this->resolveLogoUrl($school->logo),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $schools,
        ]);
    }

    /**
     * Return an absolute URL for an Organization logo path, or null when missing.
     * Some records store an absolute URL (S3); others store a relative path
     * served via asset().
     */
    private function resolveLogoUrl(?string $logo): ?string
    {
        if (! $logo) {
            return null;
        }
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }
        return asset($logo);
    }

    /** GET /api/website/testimonials */
    public function testimonials()
    {
        $reviews = RateLms::with('organization:id,name,logo')
            ->where('status', 1)
            ->latest()
            ->get()
            ->map(function ($r) {
                $name = $r->organization?->name ?? 'Anonymous';
                $logo = $r->organization?->logo;
                $feedback = trim($r->feedback ?? '', '"\'');

                // First letter of up to the first two non-empty words (UTF-8 safe).
                $words    = array_values(array_filter(preg_split('/\s+/', trim($name)), 'strlen'));
                $initials = '';
                foreach (array_slice($words, 0, 2) as $word) {
                    $initials .= strtoupper(mb_substr($word, 0, 1));
                }

                return [
                    'id'           => $r->id,
                    'feedback'     => $feedback,
                    'rating'       => $r->rating,
                    'school_name'  => $name,
                    'logo'        => $logo,
                    'logo_url'     => $this->resolveLogoUrl($logo),
                    'initials'     => $initials ?: 'S',
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    /** GET /api/website/privacy-policy */
    public function privacyPolicy()
    {
        $policy = PrivacyPolicy::first();

        return response()->json([
            'success' => true,
            'data'    => $policy ? [
                'sections'     => $policy->metadata['sections'] ?? [],
                'last_updated' => $policy->last_updated?->format('d M Y'),
            ] : null,
        ]);
    }

    /** GET /api/website/terms-conditions */
    public function termsConditions()
    {
        $tc = TermAndCondition::first();

        return response()->json([
            'success' => true,
            'data'    => $tc ? [
                'platform_name' => $tc->platform_name,
                'company_name'  => $tc->company_name,
                'sections'      => $tc->metadata['sections'] ?? [],
                'last_updated'  => $tc->last_updated?->format('d M Y'),
            ] : null,
        ]);
    }

    /** GET /api/website/terms-of-use */
    public function termsOfUse()
    {
        $tou = TermOfUse::first();

        return response()->json([
            'success' => true,
            'data'    => $tou ? [
                'sections'     => $tou->metadata['sections'] ?? [],
                'last_updated' => $tou->last_updated?->format('d M Y'),
            ] : null,
        ]);
    }

    /**
     * GET /api/website/page/{slug}
     * Returns the metadata for a dynamic marketing page
     * (why-us, services, careers, become-executive, blogs, faqs).
     */
    public function page(string $slug)
    {
        $page = WebsitePage::where('slug', $slug)->first();

        return response()->json([
            'success' => true,
            'data'    => $page ? array_merge(
                $page->metadata ?? [],
                ['last_updated' => $page->last_updated?->format('d M Y')]
            ) : null,
        ]);
    }

    /**
     * Spam guard for the public lead forms.
     *
     * Two cheap, high-signal checks:
     *   1. Honeypot — a hidden "company" field that real users never see or
     *      fill. Bots that auto-fill every field trip it.
     *   2. Reserved / test email domains (RFC 2606 example.*, plus common
     *      throwaway domains) can never be a genuine lead, so we drop them.
     *
     * Returns true when the submission should be silently discarded (we still
     * answer 200 so bots/scripts get no signal that they were blocked).
     */
    protected function isSpamSubmission(Request $request): bool
    {
        if (filled($request->input('company'))) {
            return true;
        }

        $email  = strtolower(trim((string) $request->input('email')));
        $domain = substr((string) strrchr($email, '@'), 1);

        $blockedDomains = [
            'example.com', 'example.org', 'example.net',
            'test.com', 'test.test', 'mailinator.com', 'localhost', 'invalid',
        ];

        return $domain !== '' && in_array($domain, $blockedDomains, true);
    }

    /** POST /api/website/school-contact — enquiry from a school's own website */
    public function schoolContact(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:30',
            'subject'         => 'nullable|string|max:255',
            'message'         => 'nullable|string|max:5000',
        ]);

        if ($this->isSpamSubmission($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your message has been sent. We will get back to you soon.',
            ]);
        }

        SchoolWebsiteEnquiry::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your message has been sent. We will get back to you soon.',
        ]);
    }

    /** POST /api/website/contact */
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'full_name'    => 'required|string|max:40',
            'school_name'  => 'required|string|max:100',
            'phone_number' => ['required', 'string', 'max:10', 'regex:/^[6-9][0-9]{9}$/'],
            'email'        => 'required|email|max:75',
            'subject'      => 'required|string|max:255',
            'description'  => 'required|string|max:2000',
        ]);

        if ($this->isSpamSubmission($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully! We will get back to you within 3 business days.',
            ]);
        }

        WebsiteContact::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully! We will get back to you within 3 business days.',
        ]);
    }

    /** POST /api/website/demo */
    public function demo(Request $request)
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:40',
            'school_name'    => 'required|string|max:100',
            'phone'          => ['required', 'string', 'max:10', 'regex:/^[6-9][0-9]{9}$/'],
            'email'          => 'required|email|max:75',
            'city'           => 'required|string|max:50',
            'no_of_students' => 'required|string|max:50',
            'role'           => 'required|string|max:100',
        ]);

        if ($this->isSpamSubmission($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Demo request received! Our team will contact you within 3 business days.',
            ]);
        }

        WebsiteDemo::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Demo request received! Our team will contact you within 3 business days.',
        ]);
    }

    /** POST /api/website/schedule-call — book a call at a preferred date + timeslot */
    public function scheduleCall(Request $request)
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:40',
            'school_name'    => 'required|string|max:100',
            'phone'          => ['required', 'string', 'max:10', 'regex:/^[6-9][0-9]{9}$/'],
            'email'          => 'required|email|max:75',
            'city'           => 'required|string|max:50',
            'no_of_students' => 'required|string|max:50',
            'role'           => 'required|string|max:100',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'required|string|max:50',
        ]);

        if ($this->isSpamSubmission($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Call scheduled! Our team will call you at your chosen time.',
            ]);
        }

        WebsiteDemo::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Call scheduled! Our team will call you at your chosen time.',
        ]);
    }

    /** POST /api/website/career-apply */
    public function careerApply(Request $request)
    {
        $validated = $request->validate([
            'job_role'      => 'nullable|string|max:255',
            'full_name'     => 'required|string|max:50',
            'email'         => 'required|email|max:50',
            'mobile'        => ['required', 'string', 'max:10', 'regex:/^[6-9][0-9]{9}$/'],
            'address'       => 'required|string|max:200',
            'experience'    => 'required|string|max:200',
            'qualification' => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:500',
            'document'      => 'required|file|mimes:pdf,doc,docx|max:2048', // 2 MB
        ], [
            'mobile.regex'      => 'Please enter a valid 10-digit mobile number.',
            'document.required' => 'Please attach your resume.',
            'document.mimes'    => 'Resume must be a PDF or Word document.',
            'document.max'      => 'Resume must not be larger than 2 MB.',
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('website/career-docs', 's3');
        }

        \App\Models\CareerApplication::create([
            'job_role'      => $validated['job_role'] ?? null,
            'full_name'     => $validated['full_name'],
            'email'         => $validated['email'],
            'mobile'        => $validated['mobile'],
            'address'       => $validated['address'],
            'experience'    => $validated['experience'],
            'qualification' => $validated['qualification'] ?? null,
            'description'   => $validated['description'] ?? null,
            'document_path' => $documentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application received! Our team will review it and reach out to you soon.',
        ]);
    }

    /** POST /api/website/executive-apply */
    public function executiveApply(Request $request)
    {
        $validated = $request->validate([
            'full_name'     => 'required|string|max:50',
            'email'         => 'required|email|max:50',
            'mobile'        => ['required', 'string', 'max:10', 'regex:/^[6-9][0-9]{9}$/'],
            'address'       => 'required|string|max:200',
            'qualification' => 'required|string|max:255',
            'description'   => 'nullable|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5 MB
        ], [
            'mobile.regex' => 'Please enter a valid 10-digit mobile number.',
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('website/executive-docs', 's3');
        }

        \App\Models\ExecutiveApplication::create([
            'full_name'     => $validated['full_name'],
            'email'         => $validated['email'],
            'mobile'        => $validated['mobile'],
            'address'       => $validated['address'],
            'qualification' => $validated['qualification'],
            'description'   => $validated['description'] ?? null,
            'document_path' => $documentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application received! Our partnerships team will review it and reach out to you soon.',
        ]);
    }
}
