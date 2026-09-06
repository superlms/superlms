<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['organization_id', 'exam_name', 'term', 'academic_year', 'start_date', 'end_date', 'description', 'is_published', 'exam_type', 'total_marks', 'passing_marks', 'created_by', 'updated_by', 'status'];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
    ];

    /** The five states an exam can read as. Derived, never stored. */
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UPCOMING  = 'upcoming';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';

    /** How many days before the start date an exam starts reading "Upcoming". */
    public const UPCOMING_WINDOW_DAYS = 10;

    /**
     * Status implied by the exam's dates, evaluated fresh on every read so a
     * list never goes stale as days roll over:
     *
     *   not published            → Draft
     *   end date already passed  → Completed
     *   started, not yet ended   → Active
     *   starts within 10 days    → Upcoming
     *   anything further out     → Published
     */
    public function currentStatus(): string
    {
        if (!$this->is_published) {
            return self::STATUS_DRAFT;
        }

        $today = now()->startOfDay();

        if ($this->end_date && $this->end_date->lt($today)) {
            return self::STATUS_COMPLETED;
        }

        if ($this->start_date && $this->start_date->lte($today)) {
            return self::STATUS_ACTIVE;
        }

        if ($this->start_date && $this->start_date->lte($today->copy()->addDays(self::UPCOMING_WINDOW_DAYS))) {
            return self::STATUS_UPCOMING;
        }

        return self::STATUS_PUBLISHED;
    }

    /** Human label for the current status ("Completed", "Upcoming", …). */
    public function statusLabel(): string
    {
        return ucfirst($this->currentStatus());
    }

    public function isCompleted(): bool
    {
        return $this->currentStatus() === self::STATUS_COMPLETED;
    }

    /**
     * Narrow a query to one derived status — the SQL twin of currentStatus(),
     * so filters and counts always agree with the badge on screen.
     */
    public function scopeWithStatus($query, ?string $status)
    {
        if (!$status) {
            return $query;
        }

        $today  = now()->startOfDay()->toDateString();
        $window = now()->startOfDay()->addDays(self::UPCOMING_WINDOW_DAYS)->toDateString();

        $notEnded = fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);

        return match ($status) {
            self::STATUS_DRAFT => $query->where('is_published', false),

            self::STATUS_COMPLETED => $query->where('is_published', true)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today),

            self::STATUS_ACTIVE => $query->where('is_published', true)
                ->whereNotNull('start_date')
                ->whereDate('start_date', '<=', $today)
                ->where($notEnded),

            self::STATUS_UPCOMING => $query->where('is_published', true)
                ->whereNotNull('start_date')
                ->whereDate('start_date', '>', $today)
                ->whereDate('start_date', '<=', $window)
                ->where($notEnded),

            // "Published" is what's left: live, but still further out than the
            // upcoming window (or carrying no start date at all).
            self::STATUS_PUBLISHED => $query->where('is_published', true)
                ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '>', $window))
                ->where($notEnded),

            default => $query,
        };
    }

    public function Organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }
}
