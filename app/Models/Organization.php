<?php

namespace App\Models;

use App\Models\Admin\SchoolInfo;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Organization extends Model
{
    /** Per-request cache of whether the module_organization table exists. */
    private static ?bool $moduleTableExists = null;

    /** Per-instance cache of this org's module_key => enabled map. */
    protected ?array $moduleMapCache = null;

    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'state',
        'education_board',
        'school_code',
        'affiliation_no',
        'udise_number',
        'serial_number',
        'status',
        'logo',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_branch',
        'bank_holder_name',
    ];

    protected $casts = ['status' => 'boolean'];

    protected $table = 'organizations';

    /**
     * When a school is deleted, its LMS ratings/reviews go with it — no
     * matter which code path triggered the delete.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $organization) {
            \App\Models\Admin\RateLms::where('organization_id', $organization->id)->delete();
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function schoolInfo()
    {
        return $this->hasOne(SchoolInfo::class, 'organization_id');
    }
    public function students()
    {
        return $this->hasMany(StudentDetail::class, 'organization_id');
    }
    public function teachers()
    {
        return $this->hasMany(TeacherDetail::class, 'organization_id');
    }
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    // ─── Per-school module (feature) access ────────────────────────────────

    public function modules()
    {
        return $this->hasMany(OrganizationModule::class, 'organization_id');
    }

    /**
     * Is a given module enabled for this school?
     *
     * Defaults to TRUE so existing schools (with no saved config) keep every
     * feature. Unknown / core keys are always enabled. Fails OPEN if the
     * config table is missing or unreadable — never hides features on error.
     */
    public function hasModule(string $key): bool
    {
        $modules = config('modules', []);

        // Core feature (not toggleable) → always available.
        if (!array_key_exists($key, $modules)) {
            return true;
        }

        $map = $this->moduleMap();

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        // No explicit row saved yet → use the module's default (ON).
        return (bool) ($modules[$key]['default'] ?? true);
    }

    /** Load (and cache) this school's saved module_key => enabled(bool) map. */
    protected function moduleMap(): array
    {
        if ($this->moduleMapCache !== null) {
            return $this->moduleMapCache;
        }

        if (self::$moduleTableExists === null) {
            try {
                self::$moduleTableExists = Schema::hasTable('module_organization');
            } catch (\Throwable $e) {
                self::$moduleTableExists = false;
            }
        }

        if (!self::$moduleTableExists || !$this->id) {
            return $this->moduleMapCache = [];
        }

        try {
            return $this->moduleMapCache = OrganizationModule::query()
                ->where('organization_id', $this->id)
                ->pluck('enabled', 'module_key')
                ->map(fn($v) => (bool) $v)
                ->toArray();
        } catch (\Throwable $e) {
            return $this->moduleMapCache = [];
        }
    }
}
