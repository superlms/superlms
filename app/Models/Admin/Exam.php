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

    /** Values persisted on exams.status. */
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Status the exam's dates imply: a published exam whose end date is
     * already behind us reads as "Completed", anything else stays active.
     *
     * This is only ever re-evaluated when an exam's dates are edited — the
     * list never flips a status on its own just because a day rolled over.
     */
    public function statusForCurrentDates(): string
    {
        $hasEnded = $this->end_date && $this->end_date->lt(now()->startOfDay());

        return $hasEnded && $this->is_published
            ? self::STATUS_COMPLETED
            : self::STATUS_ACTIVE;
    }

    /** True when the exam is currently flagged completed. */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
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
