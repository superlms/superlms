<?php

namespace App\Models\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContactSuperAdmin extends Model
{
    protected $fillable = ['user_id', 'organization_id', 'topic', 'admin_query', 'image', 'super_admin_text', 'super_admin_reply', 'super_admin_attachment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
