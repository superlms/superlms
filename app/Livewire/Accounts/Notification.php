<?php

namespace App\Livewire\Accounts;

use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notification extends Component
{
    // ─── Compose Form ────────────────────────────────────────────────────────────
    public string $title       = '';
    public string $body        = '';
    public string $targetType  = 'all_students'; // all_students | all_teachers | all | by_class
    public string $targetClass = '';

    // ─── Result ─────────────────────────────────────────────────────────────────
    public ?int  $lastSentCount  = null;
    public bool  $sending        = false;

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function updatedTargetType(): void
    {
        $this->targetClass = '';
    }

    public function send(): void
    {
        $this->validate([
            'title'      => 'required|string|max:255',
            'body'       => 'required|string|max:1000',
            'targetType' => 'required|string',
        ]);

        if ($this->targetType === 'by_class') {
            $this->validate(['targetClass' => 'required|exists:standards,id']);
        }

        $orgId = $this->orgId();
        $userIds = collect();

        if ($this->targetType === 'all_students') {
            $userIds = StudentDetail::where('organization_id', $orgId)
                ->whereNotNull('user_id')
                ->pluck('user_id');
        } elseif ($this->targetType === 'all_teachers') {
            $userIds = TeacherDetail::where('organization_id', $orgId)
                ->whereNotNull('user_id')
                ->pluck('user_id');
        } elseif ($this->targetType === 'by_class') {
            $userIds = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $this->targetClass)
                ->whereNotNull('user_id')
                ->pluck('user_id');
        } elseif ($this->targetType === 'all') {
            $studentUserIds = StudentDetail::where('organization_id', $orgId)
                ->whereNotNull('user_id')->pluck('user_id');
            $teacherUserIds = TeacherDetail::where('organization_id', $orgId)
                ->whereNotNull('user_id')->pluck('user_id');
            $userIds = $studentUserIds->merge($teacherUserIds)->unique();
        }

        $tokens = UserFcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();
        $sent   = 0;

        if (!empty($tokens)) {
            try {
                $service = app(FirebaseNotificationService::class);
                foreach (User::whereIn('id', $userIds)->get() as $user) {
                    if ($service->sendToUser($user, $this->title, $this->body)) {
                        $sent++;
                    }
                }
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to send notifications: ' . $e->getMessage());
                return;
            }
        }

        $this->lastSentCount = $sent;
        $this->title         = '';
        $this->body          = '';
        $this->targetType    = 'all_students';
        $this->targetClass   = '';

        session()->flash('success', "Notification sent to {$sent} device(s) successfully!");
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('order')->get();

        $totalStudents  = StudentDetail::where('organization_id', $orgId)->count();
        $totalTeachers  = TeacherDetail::where('organization_id', $orgId)->count();
        $registeredDevices = UserFcmToken::whereHas('user')->count();

        return view('livewire.accounts.notification', compact(
            'standards', 'totalStudents', 'totalTeachers', 'registeredDevices'
        ));
    }
}
