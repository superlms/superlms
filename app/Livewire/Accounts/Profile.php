<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\SchoolUser;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Profile extends Component
{
    public $user = [];
    public $schoolUser = [];
    public $organization = [];
    public array $analytics = [];

    public function mount(): void
    {
        $authUser = Auth::user();
        $orgId    = $this->orgId();

        $schoolUserRecord = SchoolUser::where('user_id', $authUser->id)
            ->where('organization_id', $orgId)
            ->first();

        // Prefer the user account photo, fall back to the staff record photo
        $avatar = $authUser->image ?: ($schoolUserRecord->image ?? null);

        $this->user = [
            'name'          => $authUser->name,
            'email'         => $authUser->email,
            'role'          => $authUser->role,
            'phone'         => $authUser->mobile_number ?? '-',
            'created_at'    => $authUser->created_at?->format('d M Y'),
            'image'         => $avatar,
            'last_login_at' => $authUser->last_login_at
                ? $authUser->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                : null,
        ];

        if ($schoolUserRecord) {
            $this->schoolUser = [
                'employee_id'      => $schoolUserRecord->employee_id ?? '-',
                'designation'      => $schoolUserRecord->designation ?? '-',
                'department'       => $schoolUserRecord->department ?? '-',
                'alternate_mobile' => $schoolUserRecord->alternate_mobile ?? '-',
                'address'          => $schoolUserRecord->address ?? '-',
                'is_active'        => $schoolUserRecord->is_active,
                'image'            => $avatar,
            ];
        }

        $org = Organization::find($orgId);
        if ($org) {
            $this->organization = [
                'name'               => $org->name ?? '-',
                'email'              => $org->email ?? '-',
                'phone'              => $org->mobile_number ?? '-',
                'address'            => $org->address ?? '-',
                'state'              => $org->state ?? '-',
                'logo'               => $org->logo ?? null,
                'school_code'        => $org->school_code ?? '-',
                'affiliation_number' => $org->affiliation_no ?? '-',
                'serial_number'      => $org->serial_number ?? '-',
                'udise_number'       => $org->udise_number ?? '-',
                'board'              => $org->education_board ?? '-',
                'created_at'         => $org->created_at?->format('d M Y'),
            ];
        }

        // Analytics: student & teacher counts for this organization
        $this->analytics = [
            'students' => StudentDetail::where('organization_id', $orgId)->count(),
            'teachers' => TeacherDetail::where('organization_id', $orgId)->count(),
            'staff'    => SchoolUser::where('organization_id', $orgId)->where('is_active', true)->count(),
        ];
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.profile');
    }
}
