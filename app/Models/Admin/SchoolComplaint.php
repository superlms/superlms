<?php

namespace App\Models\Admin;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Admin → More → Complaints: a complaint about one of the school's students. */
class SchoolComplaint extends Model
{
    protected $fillable = [
        'organization_id', 'student_detail_id', 'standard_id', 'section_id',
        'title', 'description', 'file_path', 'file_name', 'added_by',
    ];

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

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** The attached file's link, or null. */
    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('s3')->url($this->file_path) : null;
    }

    /** Whether the attachment is a picture (shown in the View panel). */
    public function fileIsImage(): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) ($this->file_name ?: $this->file_path));
    }
}
