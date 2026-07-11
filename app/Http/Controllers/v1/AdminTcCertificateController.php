<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\CertificatePdfController as WebCertificatePdfController;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Http\Request;

/**
 * School-admin TC & Certificate module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/TcCertificate.php — three tabs (achievement /
 * participation certificates + transfer certificates), per-tab analytics, a
 * class/section student picker for the issue forms, full CRUD for both entities
 * and preview. PDF downloads delegate to the web CertificatePdfController (same
 * blades), scoped by the authenticated user's organization.
 */
class AdminTcCertificateController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    public array $conductOptions = ['Excellent', 'Good', 'Satisfactory', 'Poor'];
    public array $failedOptions   = ['No', 'Once', 'Twice'];
    public array $nccOptions       = ['No', 'NCC Cadet', 'Boy Scout', 'Girl Guide'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    // ══════════════════════════ LOOKUPS + STATS ══════════════════════════

    /** GET /admin/tc-certificate/lookups — classes (with sections) + form option lists. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'sections' => Section::where('standard_id', $s->id)->where('organization_id', $orgId)
                    ->orderBy('id')->get(['id', 'name'])->toArray(),
            ]);

        return $this->success([
            'classes'         => $classes,
            'conduct_options' => $this->conductOptions,
            'failed_options'  => $this->failedOptions,
            'ncc_options'     => $this->nccOptions,
        ], 'TC & Certificate lookups fetched.');
    }

    /** GET /admin/tc-certificate/stats?tab=&standard_id=&section_id= — tab totals + per-tab analytics. */
    public function stats(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $tab = $request->input('tab', 'achievement');

        $statistics = [
            'achievement'   => Certificate::where('organization_id', $orgId)->where('type', 'achievement')->count(),
            'participation' => Certificate::where('organization_id', $orgId)->where('type', 'participation')->count(),
            'tc'            => TransferCertificate::where('organization_id', $orgId)->count(),
        ];

        if ($tab === 'tc') {
            $base = TransferCertificate::where('organization_id', $orgId);
            $dateCol = 'issue_date';
        } else {
            $base = Certificate::where('organization_id', $orgId)->where('type', $tab);
            $dateCol = 'issued_date';
        }
        if ($request->filled('standard_id')) {
            $base->whereHas('student', fn ($q) => $q->where('standard_id', $request->standard_id));
        }
        if ($request->filled('section_id')) {
            $base->whereHas('student', fn ($q) => $q->where('section_id', $request->section_id));
        }

        $now = now();
        $lastMon = $now->copy()->subMonthNoOverflow();

        return $this->success([
            'statistics' => $statistics,
            'analytics'  => [
                'total'      => (clone $base)->count(),
                'this_month' => (clone $base)->whereYear($dateCol, $now->year)->whereMonth($dateCol, $now->month)->count(),
                'last_month' => (clone $base)->whereYear($dateCol, $lastMon->year)->whereMonth($dateCol, $lastMon->month)->count(),
                'this_week'  => (clone $base)->whereBetween($dateCol, [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count(),
            ],
        ], 'TC & Certificate stats fetched.');
    }

    /** GET /admin/tc-certificate/students?standard_id=&section_id=&search= — student picker for issue forms. */
    public function students(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['standard_id' => 'required|integer'])) return $err;

        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $user->organization_id)
            ->where('standard_id', $request->standard_id)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($w) =>
                $w->where('full_name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%")))
            ->orderBy('full_name')->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'full_name'    => $s->full_name,
                'admission_no' => $s->admission_no,
                'class'        => trim(($s->standard?->name ?? '') . ' ' . ($s->section?->name ?? '')),
            ]);

        return $this->success(['students' => $students], 'Students fetched.');
    }

    // ══════════════════════════ LIST ══════════════════════════

    /** GET /admin/tc-certificate?tab=achievement|participation|tc&search=&standard_id=&section_id=&month=&per_page=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $tab = $request->input('tab', 'achievement');
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        if ($tab === 'tc') {
            $q = TransferCertificate::with('student:id,full_name,admission_no,standard_id,section_id')
                ->where('organization_id', $orgId);
            if ($search) {
                $q->where(fn ($sq) => $sq->where('tc_no', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")));
            }
            $this->applyStudentFilters($q, $request);
            $this->applyMonthFilter($q, $request, 'issue_date');
            $paginator = $q->orderByDesc('issue_date')->paginate($perPage);
            $items = collect($paginator->items())->map(fn ($tc) => $this->presentTc($tc));
        } else {
            $q = Certificate::with('student:id,full_name,admission_no,standard_id,section_id')
                ->where('organization_id', $orgId)->where('type', $tab);
            if ($search) {
                $q->where(fn ($sq) => $sq->where('event_name', 'like', "%{$search}%")
                    ->orWhere('certificate_no', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")));
            }
            $this->applyStudentFilters($q, $request);
            $this->applyMonthFilter($q, $request, 'issued_date');
            $paginator = $q->orderByDesc('issued_date')->paginate($perPage);
            $items = collect($paginator->items())->map(fn ($c) => $this->presentCert($c));
        }

        return $this->paginated($items, $this->paginationMeta($paginator), 'TC & Certificates fetched.');
    }

    private function applyStudentFilters($q, Request $request): void
    {
        if ($request->filled('standard_id')) {
            $q->whereHas('student', fn ($s) => $s->where('standard_id', $request->standard_id));
        }
        if ($request->filled('section_id')) {
            $q->whereHas('student', fn ($s) => $s->where('section_id', $request->section_id));
        }
    }

    private function applyMonthFilter($q, Request $request, string $col): void
    {
        if ($month = $request->input('month')) {
            [$fy, $fm] = array_pad(explode('-', $month), 2, null);
            if ($fy && $fm) $q->whereYear($col, $fy)->whereMonth($col, $fm);
        }
    }

    private function presentCert(Certificate $c): array
    {
        return [
            'id'                    => $c->id,
            'certificate_no'        => $c->certificate_no,
            'type'                  => $c->type,
            'student_id'            => $c->student_detail_id,
            'student_name'          => $c->student?->full_name,
            'admission_no'          => $c->student?->admission_no,
            'event_name'            => $c->event_name,
            'issued_by'             => $c->issued_by,
            'issued_by_designation' => $c->issued_by_designation,
            'description'           => $c->description,
            'issued_date'           => optional($c->issued_date)->format('Y-m-d'),
            'issued_label'          => optional($c->issued_date)->format('d M Y'),
            'pdf_url'               => url("/api/v1/admin/tc-certificate/cert/{$c->id}/pdf"),
        ];
    }

    private function presentTc(TransferCertificate $tc): array
    {
        return [
            'id'                      => $tc->id,
            'tc_no'                   => $tc->tc_no,
            'student_id'              => $tc->student_detail_id,
            'student_name'            => $tc->student?->full_name,
            'admission_no'            => $tc->student?->admission_no,
            'book_no'                 => $tc->book_no,
            'nationality'             => $tc->nationality,
            'is_sc_st'                => (bool) $tc->is_sc_st,
            'last_class_studied'      => $tc->last_class_studied,
            'exam_last_taken'         => $tc->exam_last_taken,
            'whether_failed'          => $tc->whether_failed,
            'subjects_studied'        => $tc->subjects_studied,
            'qualified_for_promotion' => $tc->qualified_for_promotion,
            'fees_paid_upto'          => $tc->fees_paid_upto,
            'fee_concession'          => $tc->fee_concession,
            'total_working_days'      => $tc->total_working_days,
            'days_present'            => $tc->days_present,
            'is_ncc_scout'            => $tc->is_ncc_scout,
            'extra_activities'        => $tc->extra_activities,
            'general_conduct'         => $tc->general_conduct,
            'application_date'        => optional($tc->application_date)->format('Y-m-d'),
            'issue_date'              => optional($tc->issue_date)->format('Y-m-d'),
            'issue_label'             => optional($tc->issue_date)->format('d M Y'),
            'reason_for_leaving'      => $tc->reason_for_leaving,
            'remarks'                 => $tc->remarks,
            'pdf_url'                 => url("/api/v1/admin/tc-certificate/tc/{$tc->id}/pdf"),
        ];
    }

    // ══════════════════════════ CERTIFICATE CRUD ══════════════════════════

    /** POST /admin/tc-certificate/cert */
    public function storeCert(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->certRules($request)) return $err;

        $cert = Certificate::create($this->certData($request, $user->organization_id));
        return $this->success(['certificate' => $this->presentCert($cert->load('student'))], 'Certificate issued successfully.', 201);
    }

    /** POST /admin/tc-certificate/cert/{id} */
    public function updateCert(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $cert = Certificate::where('organization_id', $user->organization_id)->find($id);
        if (!$cert) return $this->error('Certificate not found.', 404);
        if ($err = $this->certRules($request)) return $err;

        $cert->update($this->certData($request, $user->organization_id));
        return $this->success(['certificate' => $this->presentCert($cert->fresh('student'))], 'Certificate updated.');
    }

    /** DELETE /admin/tc-certificate/cert/{id} */
    public function destroyCert($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $cert = Certificate::where('organization_id', $user->organization_id)->find($id);
        if (!$cert) return $this->error('Certificate not found.', 404);

        $cert->delete();
        return $this->success(null, 'Certificate removed.');
    }

    private function certRules(Request $request): ?\Illuminate\Http\JsonResponse
    {
        return $this->validateWith($request, [
            'type'                  => 'required|in:achievement,participation',
            'student_detail_id'     => 'required|exists:student_details,id',
            'event_name'            => 'required|string|max:255',
            'issued_by'             => 'required|string|max:255',
            'issued_by_designation' => 'nullable|string|max:100',
            'description'           => 'nullable|string|max:1000',
            'issued_date'           => 'required|date',
        ]);
    }

    private function certData(Request $request, int $orgId): array
    {
        return [
            'organization_id'       => $orgId,
            'student_detail_id'     => $request->student_detail_id,
            'type'                  => $request->type,
            'event_name'            => $request->event_name,
            'issued_by'             => $request->issued_by,
            'issued_by_designation' => $request->issued_by_designation ?: null,
            'description'           => $request->description ?: null,
            'issued_date'           => $request->issued_date,
        ];
    }

    /** GET /admin/tc-certificate/cert/{id}/pdf */
    public function certPdf($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!Certificate::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Certificate not found.', 404);
        }
        return app(WebCertificatePdfController::class)->downloadCert($user->organization_id, (int) $id);
    }

    // ══════════════════════════ TRANSFER CERTIFICATE CRUD ══════════════════════════

    /** POST /admin/tc-certificate/tc */
    public function storeTc(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->tcRules($request)) return $err;

        $tc = TransferCertificate::create($this->tcData($request, $user->organization_id));
        return $this->success(['tc' => $this->presentTc($tc->load('student'))], 'Transfer Certificate issued.', 201);
    }

    /** POST /admin/tc-certificate/tc/{id} */
    public function updateTc(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $tc = TransferCertificate::where('organization_id', $user->organization_id)->find($id);
        if (!$tc) return $this->error('Transfer Certificate not found.', 404);
        if ($err = $this->tcRules($request)) return $err;

        $tc->update($this->tcData($request, $user->organization_id));
        return $this->success(['tc' => $this->presentTc($tc->fresh('student'))], 'TC updated.');
    }

    /** DELETE /admin/tc-certificate/tc/{id} */
    public function destroyTc($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $tc = TransferCertificate::where('organization_id', $user->organization_id)->find($id);
        if (!$tc) return $this->error('Transfer Certificate not found.', 404);

        $tc->delete();
        return $this->success(null, 'TC removed.');
    }

    private function tcRules(Request $request): ?\Illuminate\Http\JsonResponse
    {
        return $this->validateWith($request, [
            'student_detail_id' => 'required|exists:student_details,id',
            'application_date'  => 'required|date',
            'issue_date'        => 'required|date',
            'general_conduct'   => 'required|string',
        ]);
    }

    private function tcData(Request $request, int $orgId): array
    {
        return [
            'organization_id'         => $orgId,
            'student_detail_id'       => $request->student_detail_id,
            'book_no'                 => $request->book_no ?: null,
            'nationality'             => $request->input('nationality', 'Indian'),
            'is_sc_st'                => (bool) $request->boolean('is_sc_st'),
            'last_class_studied'      => $request->last_class_studied ?: null,
            'exam_last_taken'         => $request->exam_last_taken ?: null,
            'whether_failed'          => $request->input('whether_failed', 'No'),
            'subjects_studied'        => $request->subjects_studied ?: null,
            'qualified_for_promotion' => $request->input('qualified_for_promotion', 'Yes'),
            'fees_paid_upto'          => $request->fees_paid_upto ?: null,
            'fee_concession'          => $request->fee_concession ?: null,
            'total_working_days'      => (int) $request->input('total_working_days', 0),
            'days_present'            => (int) $request->input('days_present', 0),
            'is_ncc_scout'            => $request->input('is_ncc_scout', 'No'),
            'extra_activities'        => $request->extra_activities ?: null,
            'general_conduct'         => $request->input('general_conduct', 'Good'),
            'application_date'        => $request->application_date,
            'issue_date'              => $request->issue_date,
            'reason_for_leaving'      => $request->reason_for_leaving ?: null,
            'remarks'                 => $request->remarks ?: null,
        ];
    }

    /** GET /admin/tc-certificate/tc/{id}/pdf */
    public function tcPdf($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!TransferCertificate::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Transfer Certificate not found.', 404);
        }
        return app(WebCertificatePdfController::class)->downloadTc($user->organization_id, (int) $id);
    }
}
