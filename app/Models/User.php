<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Admin\DriverDetail;
use App\Models\Admin\HomeWork;
use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAssignment;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Traits\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasCommonScopes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number',
        'otp',
        'is_active',
        'image',
        'organization_id',
        'role',
        'otp_expires_at',
        'otp_order_id',
        'last_login_at',
        'dob',
        'gender',
        'date_of_joining',
        'alternative_mobile',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    /**
     * A sub-super-admin is a scoped super-admin created by the main
     * super-admin from the Users panel.
     */
    public function isSubSuperAdmin(): bool
    {
        return $this->role === 'sub-super-admin';
    }

    /**
     * True if this user may access the given super-admin route name.
     * Main super-admin (role super-admin) always has full access.
     * Sub-super-admins are limited to their granted permissions, with
     * profile + notification always allowed.
     */
    public function canAccessSuperAdminRoute(?string $routeName): bool
    {
        if ($this->role === 'super-admin') {
            return true;
        }

        if ($this->role !== 'sub-super-admin') {
            return false;
        }

        // Always-allowed routes for any signed-in sub-super-admin
        $always = ['super-admin.profile', 'super-admin.notification'];
        if (in_array($routeName, $always, true)) {
            return true;
        }

        return in_array($routeName, (array) $this->permissions, true);
    }

    /**
     * A sub-admin is a scoped admin created by a school admin from the
     * Users panel, limited to a subset of admin functionalities.
     */
    public function isSubAdmin(): bool
    {
        return $this->role === 'sub-admin';
    }

    /**
     * True if this user may access the given admin route name.
     * Full admins (role admin) always have access. Sub-admins are limited
     * to their granted permissions — with profile + notification always
     * allowed, and sub-routes of a granted screen (e.g. downloads/prints)
     * permitted by prefix match.
     */
    public function canAccessAdminRoute(?string $routeName): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role !== 'sub-admin') {
            return false;
        }

        if ($routeName === null) {
            return false;
        }

        // Always-allowed routes for any signed-in sub-admin
        $always = ['admin.profile', 'admin.notification', 'admin.messages'];
        if (in_array($routeName, $always, true)) {
            return true;
        }

        $granted = (array) $this->permissions;

        // Exact match, or a sub-route of a granted screen (granted "admin.report-card"
        // also allows "admin.report-card.download", "admin.report-card.print", etc.)
        foreach ($granted as $perm) {
            if ($routeName === $perm || str_starts_with($routeName, $perm . '.')) {
                return true;
            }
        }

        return false;
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    //Check Role
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }

        return !! $role->intersect($this->roles)->count();
    }

    // Assign Role
    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching($role);
    }

    //Delete Role
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->detach($role);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function teacherDetail()
    {
        return $this->hasOne(TeacherDetail::class, 'user_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }

    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class, 'teacher_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class);
    }


    public function teacherAttendance()
    {
        return $this->hasManyThrough(
            TeacherAttendance::class,
            TeacherDetail::class,
            'user_id',
            'teacher_detail_id',
            'id',
            'id'
        );
    }

    public function todayAttendance()
    {
        return $this->hasOneThrough(
            TeacherAttendance::class,
            TeacherDetail::class,
            'user_id',
            'teacher_detail_id',
            'id',
            'id'
        )->whereDate('attendance_date', today());
    }

    public function isTeacher()
    {
        return $this->role === 'teacher' && $this->teacherDetail !== null;
    }

    public function arrangedSubstitutes()
    {
        return $this->hasMany(TeacherArrangement::class, 'arranged_by');
    }

    public function assignedTimetables()
    {
        return $this->hasMany(TeacherTimeTable::class, 'assigned_by');
    }

    public function homeworks()
    {
        return $this->hasMany(HomeWork::class);
    }

    public function driverDetail()
    {
        return $this->hasOne(DriverDetail::class);
    }

    public function schoolUser()
    {
        return $this->hasOne(\App\Models\Admin\SchoolUser::class);
    }
}
