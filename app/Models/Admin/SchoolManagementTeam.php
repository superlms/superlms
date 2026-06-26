<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SchoolManagementTeam extends Model
{
    protected $fillable = [
        'school_info_id',
        'name',
        'designation',
        'photo_path',
        'sort_order'
    ];

    public function schoolInfo()
    {
        return $this->belongsTo(SchoolInfo::class);
    }
}
