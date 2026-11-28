<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\SchoolComplaint;
use App\Models\Student\StudentDetail;

/**
 * The student app's Complaints: the complaints the school has recorded about
 * the signed-in student (Admin → More → Complaints), newest first, read only —
 * each with its title, what it says, the day it was made and the file attached,
 * if any.
 */
class StudentComplaintController extends ApiController
{
    /** GET /v1/student/complaints */
    public function index()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        $studentId = StudentDetail::where('user_id', $user->id)->value('id');
        if (!$studentId || !$user->organization_id) {
            return $this->success(['complaints' => []], 'Complaints fetched.');
        }

        $complaints = SchoolComplaint::where('organization_id', $user->organization_id)
            ->where('student_detail_id', $studentId)
            ->latest('id')
            ->get()
            ->map(fn (SchoolComplaint $c) => [
                'id'          => $c->id,
                'title'       => $c->title,
                'description' => $c->description,
                'date'        => $c->created_at?->format('d M Y'),
                'file_url'    => $c->fileUrl(),
                'file_name'   => $c->file_name,
                'is_image'    => $c->fileIsImage(),
            ])
            ->values();

        return $this->success(['complaints' => $complaints], 'Complaints fetched.');
    }
}
