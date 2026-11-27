<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;

/**
 * A character certificate (चरित्र प्रमाण-पत्र), printed in Hindi or English
 * (pdf.admin.character-certificate). The student's name, father's name and
 * date of birth come from their record; what is typed here is the rest of
 * the school's form.
 */
class CharacterCertificate extends Model
{
    protected $table = 'character_certificates';

    protected $fillable = [
        'organization_id', 'student_detail_id', 'language', 'book_no', 'serial_no', 'gender',
        'student_name_hi', 'father_name_hi', 'school_name_hi', 'school_place_hi',
        'from_year', 'to_year', 'exam_year', 'exam_name', 'division', 'result',
        'took_part_unlawful', 'issue_date',
    ];

    protected $casts = [
        'took_part_unlawful' => 'boolean',
        'issue_date'         => 'date',
    ];

    public const LANGUAGES = ['hi' => 'Hindi', 'en' => 'English'];
    public const DIVISIONS = ['first' => ['प्रथम', 'First'], 'second' => ['द्वितीय', 'Second'], 'third' => ['तृतीय', 'Third']];
    public const RESULTS   = ['passed' => ['उत्तीर्ण', 'passed'], 'failed' => ['अनुत्तीर्ण', 'did not pass']];

    public function organization() { return $this->belongsTo(Organization::class); }

    public function student() { return $this->belongsTo(StudentDetail::class, 'student_detail_id'); }

    public function isHindi(): bool
    {
        return $this->language !== 'en';
    }

    public function isFemale(): bool
    {
        return $this->gender === 'female';
    }

    /** The gender a student's record gives, as the certificate's wording takes it. */
    public static function genderOf(?string $recorded): string
    {
        return str_starts_with(strtolower(trim((string) $recorded)), 'f') ? 'female' : 'male';
    }
}
