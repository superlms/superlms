<?php

namespace App\Models\Boards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A Class 9–12 student's board roll number and admit card, added in More →
 * Boards. The admit card is kept on S3 outside the public admin/ prefix and
 * opened by a link that runs out (url()).
 */
class BoardStudent extends Model
{
    protected $fillable = ['organization_id', 'student_detail_id', 'roll_number', 'admit_card_path', 'admit_card_name', 'updated_by'];

    /** A link to the admit card for a while, or null with none. */
    public function admitCardUrl(int $minutes = 120): ?string
    {
        if (! $this->admit_card_path) {
            return null;
        }
        try {
            return Storage::disk('s3')->temporaryUrl($this->admit_card_path, now()->addMinutes($minutes));
        } catch (\Throwable $e) {
            return Storage::disk('s3')->url($this->admit_card_path);
        }
    }
}
