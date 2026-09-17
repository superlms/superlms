<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamCopy extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'student_detail_id', 'standard_id', 'section_id', 'subject_id', 'teacher_detail_id', 'exam_id', 'marks_obtained', 'max_marks', 'percentage', 'grade', 'remarks', 'is_absent', 'is_recheck', 'breakup', 'file', 'pdf_path', 'uploaded_by'];

    protected $casts = [
        'breakup' => 'array',
    ];

    /**
     * The grade as the CURRENT scale reads it (config/grading.php), worked out
     * from the stored percentage rather than the `grade` column — so records
     * saved under an older scale still display today's letters.
     */
    public function getGradeLetterAttribute(): string
    {
        if ($this->is_absent) {
            return 'AB';
        }

        return app(\App\Services\GradingService::class)
            ->gradeLetter((float) $this->percentage) ?? ($this->grade ?: 'F');
    }

    /**
     * The copy's PDF as a link. The admin panel stores the S3 key and the older
     * app endpoints stored the full URL; either reads back as a URL.
     */
    public function pdfUrl(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return str_starts_with($this->pdf_path, 'http')
            ? $this->pdf_path
            : \Illuminate\Support\Facades\Storage::disk('s3')->url($this->pdf_path);
    }

    /** Remove the copy's PDF from S3, whichever way its path was stored. */
    public function deletePdfFile(): void
    {
        if (!$this->pdf_path) {
            return;
        }

        $key = str_starts_with($this->pdf_path, 'http')
            ? ltrim((string) parse_url($this->pdf_path, PHP_URL_PATH), '/')
            : $this->pdf_path;

        try {
            if ($key) {
                \Illuminate\Support\Facades\Storage::disk('s3')->delete($key);
            }
        } catch (\Throwable $e) {
            logger()->warning('exam-copy PDF delete failed: ' . $e->getMessage());
        }
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studentDetail()
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacherDetails()
    {
        return $this->belongsTo(TeacherDetail::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSubjectMarks()
    {
        return $this->hasMany(ExamSubjectMark::class, 'exam_copy_id');
    }
}
