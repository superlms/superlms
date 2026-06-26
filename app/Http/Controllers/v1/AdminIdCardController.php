<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\IdCardGenerationSetting;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\IdCardService;
use Illuminate\Http\Request;

/**
 * School-admin ID Card module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/IdCard.php — listing by type (student/teacher/
 * employee), analytics, gap-fill generation via IdCardService, edit (expiry/
 * status), delete and a flattened card view payload. Org-scoped, role-gated.
 */
class AdminIdCardController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

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

    private function service(): IdCardService
    {
        return app(IdCardService::class);
    }

    private function type(Request $request): string
    {
        $t = $request->input('type', 'student');
        return in_array($t, IdCardService::TYPES, true) ? $t : 'student';
    }

    private function modelFor(string $type): string
    {
        return $this->service()->modelClassFor($type);
    }

    private function personName($card, string $type): ?string
    {
        return match ($type) {
            'student' => $card->studentDetail->full_name ?? ($card->studentDetail->user->name ?? null),
            'teacher' => $card->teacherDetail->user->name ?? null,
            default   => $card->adminEmployee->name ?? null,
        };
    }

    private function shapeRow($card, string $type): array
    {
        $sub = null;
        if ($type === 'student') {
            $sub = trim(($card->studentDetail->standard->name ?? '') . ($card->studentDetail->section ? ' - ' . $card->studentDetail->section->name : ''));
        } elseif ($type === 'teacher') {
            $sub = $card->teacherDetail->employee_id ?? 'Teacher';
        } else {
            $sub = $card->adminEmployee->designation ?? 'Employee';
        }

        return [
            'id'          => $card->id,
            'card_number' => $card->card_number,
            'name'        => $this->personName($card, $type),
            'subtitle'    => $sub ?: ucfirst($type),
            'issue_date'  => optional($card->issue_date)->format('Y-m-d'),
            'expiry_date' => optional($card->expiry_date)->format('Y-m-d'),
            'status'      => $card->status,
        ];
    }

    /** GET /admin/id-cards?type=&search=&standard=&section=&status=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $type  = $this->type($request);
        $search = $request->input('search');

        if ($type === 'student') {
            $query = StudentIdCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section'])
                ->where('organization_id', $orgId);
            if ($search) {
                $query->where(fn ($q) => $q->where('card_number', 'like', "%$search%")
                    ->orWhereHas('studentDetail', fn ($q2) => $q2->where('full_name', 'like', "%$search%")
                        ->orWhere('admission_no', 'like', "%$search%")->orWhere('email', 'like', "%$search%")));
            }
            if ($request->filled('standard')) {
                $query->whereHas('studentDetail', fn ($q) => $q->where('standard_id', $request->standard));
            }
            if ($request->filled('section')) {
                $query->whereHas('studentDetail', fn ($q) => $q->where('section_id', $request->section));
            }
        } elseif ($type === 'teacher') {
            $query = TeacherIdCard::with(['teacherDetail.user'])->where('organization_id', $orgId);
            if ($search) {
                $query->where(fn ($q) => $q->where('card_number', 'like', "%$search%")
                    ->orWhereHas('teacherDetail', fn ($q2) => $q2->where('employee_id', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhereHas('user', fn ($q3) => $q3->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%"))));
            }
        } else {
            $query = EmployeeIdCard::with(['adminEmployee'])->where('organization_id', $orgId);
            if ($search) {
                $query->where(fn ($q) => $q->where('card_number', 'like', "%$search%")
                    ->orWhereHas('adminEmployee', fn ($q2) => $q2->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")->orWhere('mobile', 'like', "%$search%")
                        ->orWhere('designation', 'like', "%$search%")));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->latest()->paginate((int) $request->input('per_page', 100));

        // Lookups for the student filters.
        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('order')->get(['id', 'name']);
        $sections = $request->filled('standard')
            ? Section::where('standard_id', $request->standard)->orderBy('name')->get(['id', 'name'])
            : collect();

        return $this->success([
            'type'       => $type,
            'cards'      => collect($paginator->items())->map(fn ($c) => $this->shapeRow($c, $type)),
            'pagination' => $this->paginationMeta($paginator),
            'analytics'  => $this->analytics($orgId, $type),
            'standards'  => $standards,
            'sections'   => $sections,
        ], 'ID cards fetched.');
    }

    private function analytics(int $orgId, string $type): array
    {
        switch ($type) {
            case 'student':
                $total  = StudentDetail::where('organization_id', $orgId)->count();
                $issued = StudentIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->distinct('student_detail_id')->count('student_detail_id');
                break;
            case 'teacher':
                $total  = TeacherDetail::where('organization_id', $orgId)->count();
                $issued = TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->distinct('teacher_detail_id')->count('teacher_detail_id');
                break;
            default:
                $total  = AdminEmployee::where('organization_id', $orgId)->count();
                $issued = EmployeeIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->distinct('admin_employee_id')->count('admin_employee_id');
        }
        return ['total' => $total, 'issued' => $issued, 'remaining' => max(0, $total - $issued)];
    }

    /** POST /admin/id-cards/generate  (type, expiry_date, standard_ids[]) */
    public function generate(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'type'          => 'required|in:student,teacher,employee',
            'expiry_date'   => 'required|date|after:today',
            'standard_ids'  => 'nullable|array',
        ], ['expiry_date.after' => 'Expiry date must be in the future.'])) return $err;

        try {
            $organization = $user->organization;
            if (!$organization) return $this->error('Organization not found.', 404);

            $standardIds = $request->type === 'student'
                ? array_values(array_filter((array) $request->input('standard_ids', [])))
                : null;

            $result = $this->service()->generateForType(
                $organization, $request->type, $request->expiry_date, $standardIds, $user->id
            );

            IdCardGenerationSetting::updateOrCreate(
                ['organization_id' => $organization->id, 'type' => $request->type],
                ['auto_enabled' => true, 'expiry_date' => $request->expiry_date],
            );

            return $this->success([
                'generated' => $result['generated'],
                'errors'    => array_slice($result['errors'] ?? [], 0, 5),
            ], $result['generated'] > 0
                ? "Generated {$result['generated']} ID card(s)."
                : 'All selected ' . $request->type . 's already have an active ID card.');
        } catch (\Throwable $e) {
            return $this->error('Failed to generate cards: ' . $e->getMessage(), 500);
        }
    }

    /** GET /admin/id-cards/{type}/{id} — flattened card view payload. */
    public function show(Request $request, $type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = in_array($type, IdCardService::TYPES, true) ? $type : 'student';
        $orgId = $user->organization_id;

        if ($type === 'student') {
            $card = StudentIdCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->studentDetail;
        } elseif ($type === 'teacher') {
            $card = TeacherIdCard::with(['teacherDetail.user', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->teacherDetail;
        } else {
            $card = EmployeeIdCard::with(['adminEmployee', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->adminEmployee;
        }

        if (!$card) return $this->error('Card not found.', 404);

        if (!$card->qr_code && $person) {
            $qr = $this->service()->generateQrCode($card, $person, $card->organization, $type);
            if ($qr) $card->update(['qr_code' => $qr]);
        }

        $data = $this->service()->cardViewData($card->fresh(), $type);
        // Embed the QR as a data-URI so the app can render it directly.
        $data['qr_code'] = $card->qr_code ? 'data:image/png;base64,' . $card->qr_code : null;

        return $this->success($data, 'Card fetched.');
    }

    /** PUT /admin/id-cards/{type}/{id}  (expiry_date, status) */
    public function update(Request $request, $type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = in_array($type, IdCardService::TYPES, true) ? $type : 'student';

        if ($err = $this->validateWith($request, [
            'expiry_date' => 'required|date',
            'status'      => 'required|in:active,inactive',
        ])) return $err;

        $model = $this->modelFor($type);
        $card  = $model::where('organization_id', $user->organization_id)->find($id);
        if (!$card) return $this->error('Card not found.', 404);

        $card->update(['expiry_date' => $request->expiry_date, 'status' => $request->status]);

        return $this->success(['id' => $card->id], 'ID card updated.');
    }

    /** DELETE /admin/id-cards/{type}/{id} */
    public function destroy($type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = in_array($type, IdCardService::TYPES, true) ? $type : 'student';

        $model = $this->modelFor($type);
        $card  = $model::where('organization_id', $user->organization_id)->find($id);
        if (!$card) return $this->error('Card not found.', 404);

        $card->delete();
        return $this->success(null, 'ID card deleted successfully!');
    }
}
