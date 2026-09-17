<?php

namespace App\Models\Admin;

use App\Models\Admin\AdminAttendance;
use App\Models\Organization;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;

class AdminEmployee extends Model
{
    use HasCommonScopes;

    protected $fillable = [
        'name',
        'organization_id',
        'teacher_detail_id',
        'driver_detail_id',
        'email',
        'mobile',
        'designation',
        'type',
        'salary',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_holder_name',
        'bank_branch',
        'bank_ifsc',
        'photo',
        'is_active',
        'joining_date',
    ];

    protected $hidden = [
        'password',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function teacherDetail()
    {
        return $this->belongsTo(TeacherDetail::class);
    }

    public function driverDetail()
    {
        return $this->belongsTo(DriverDetail::class);
    }

    public function idCard()
    {
        return $this->hasOne(EmployeeIdCard::class, 'admin_employee_id');
    }

    public function idCards()
    {
        return $this->hasMany(EmployeeIdCard::class, 'admin_employee_id');
    }

    public const TEACHER_STATUS_MAP = [
        'present'  => 1,
        'absent'   => 0,
        'late'     => 2,
        'half_day' => 3,
    ];

    public function isTeacher(): bool
    {
        return $this->type === 'teacher';
    }

    /**
     * Every role this one person holds: the row's own type first, then teacher
     * and driver when a teacher or driver record is linked — a teacher who also
     * drives a bus is one row that reads "teacher, driver".
     */
    public function types(): array
    {
        $types = [$this->type];
        if ($this->teacher_detail_id) $types[] = 'teacher';
        if ($this->driver_detail_id) $types[] = 'driver';

        return array_values(array_unique($types));
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->types(), true);
    }

    /**
     * A driver row made from a Transport driver and nothing else — the kind
     * that folds into the same person's teacher, management or employee row.
     */
    public function isLinkedDriverOnly(): bool
    {
        return $this->type === 'driver' && $this->driver_detail_id && !$this->teacher_detail_id;
    }

    /**
     * Whether two rows are the same person. Teachers and drivers are separate
     * user accounts (a driver always gets a new one), so the person is known by
     * the same user account, or else by a shared 10-digit mobile number
     * together with the same first name.
     */
    public function isSamePersonAs(self $other): bool
    {
        $mine   = $this->userIds();
        $theirs = $other->userIds();
        if (array_intersect($mine, $theirs)) {
            return true;
        }

        $first = self::firstName($this->personName());

        return $first !== ''
            && $first === self::firstName($other->personName())
            && array_intersect($this->mobileKeys(), $other->mobileKeys());
    }

    private function userIds(): array
    {
        return array_values(array_filter([
            $this->teacher_detail_id ? $this->teacherDetail?->user_id : null,
            $this->driver_detail_id ? $this->driverDetail?->user_id : null,
        ]));
    }

    private function personName(): string
    {
        return (string) ($this->name ?: ($this->teacherDetail?->user?->name ?? $this->driverDetail?->user?->name ?? ''));
    }

    /** Every mobile number known for the person, as its last 10 digits. */
    private function mobileKeys(): array
    {
        $numbers = [$this->mobile];
        if ($this->teacher_detail_id) {
            $numbers[] = $this->teacherDetail?->phone;
            $numbers[] = $this->teacherDetail?->user?->mobile_number;
        }
        if ($this->driver_detail_id) {
            $numbers[] = $this->driverDetail?->phone;
            $numbers[] = $this->driverDetail?->user?->mobile_number;
        }

        $keys = [];
        foreach ($numbers as $n) {
            $digits = preg_replace('/\D+/', '', (string) $n);
            if (strlen($digits) >= 10) {
                $keys[] = substr($digits, -10);
            }
        }

        return array_values(array_unique($keys));
    }

    /** The first word of a name, past any title (Mr., Dr., Smt. …). */
    private static function firstName(string $name): string
    {
        $titles = ['mr', 'mrs', 'ms', 'miss', 'dr', 'shri', 'sri', 'smt', 'sh'];
        foreach (preg_split('/[\s.]+/u', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (!in_array($word, $titles, true)) {
                return $word;
            }
        }

        return '';
    }

    public function getAttendanceStatusForDate(string $date): ?string
    {
        if ($this->isTeacher() && $this->teacher_detail_id) {
            $attendance = TeacherAttendance::where('teacher_detail_id', $this->teacher_detail_id)
                ->whereDate('attendance_date', $date)
                ->first();

            return $attendance?->statusLabel;
        }

        $attendance = AdminAttendance::where('admin_employee_id', $this->id)
            ->whereDate('date', $date)
            ->first();

        return $attendance?->status;
    }
}
