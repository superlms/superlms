<?php

namespace App\Livewire\Admin;

use App\Models\Biometric\BiometricDevice;
use App\Support\Biometric;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Setting extends Component
{
    use WireUiActions;

    public $organization = null;

    // ─── Biometric attendance (when the super admin has it on) ───────────
    // Students: one time — a punch by it is Present, a later one Absent.
    // Teachers: the punch-in by, and the punch-out from; the times are kept either way.
    public string $studentLastTime = '';
    public string $teacherInTime   = '';
    public string $teacherOutTime  = '';

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization_id;

        if ($this->biometricOn()) {
            $t = Biometric::times($this->orgId());
            $this->studentLastTime = $t['student_last_time'];
            $this->teacherInTime   = (string) $t['teacher_in_time'];
            $this->teacherOutTime  = (string) $t['teacher_out_time'];
        }
    }

    private function orgId(): int
    {
        return (int) auth()->user()?->organization_id;
    }

    private function biometricOn(): bool
    {
        return Biometric::enabled($this->orgId());
    }

    public function saveBiometric(): void
    {
        if (!$this->biometricOn()) {
            return;
        }

        $time = ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];
        $this->validate([
            'studentLastTime' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'teacherInTime'   => $time,
            'teacherOutTime'  => $time,
        ], [
            'studentLastTime.required' => 'Set the students\' last punch time.',
            '*.regex'                  => 'Enter a time, e.g. 08:30.',
        ]);

        if ($this->teacherInTime && $this->teacherOutTime && $this->teacherOutTime <= $this->teacherInTime) {
            $this->addError('teacherOutTime', 'Punch-out comes after punch-in.');
            return;
        }

        Biometric::saveTimes($this->orgId(), [
            'student_last_time' => $this->studentLastTime,
            'teacher_in_time'   => $this->teacherInTime ?: null,
            'teacher_out_time'  => $this->teacherOutTime ?: null,
        ], auth()->id());

        $this->notification()->success('Saved', 'Biometric attendance follows the new times from the next punch.');
    }

    public function render()
    {
        $on = $this->biometricOn();

        return view('livewire.admin.setting', [
            'biometricOn' => $on,
            'devices'     => $on ? BiometricDevice::where('organization_id', $this->orgId())->orderBy('id')->get() : collect(),
        ]);
    }
}
