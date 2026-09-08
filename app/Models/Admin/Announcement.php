<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'type', 'standard_id', 'announcement_name', 'announcement_content', 'announcement_image', 'announcement_pdf'];

    /**
     * The class this student announcement is aimed at. NULL = every class,
     * which is also what a teacher/all-staff announcement carries.
     */
    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
