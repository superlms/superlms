<?php

namespace App\Http\Controllers\Admin;

/**
 * A teacher's photo, from this site — whole, for the photo editor on the web
 * panel's Teachers page (a picture from the media host may not be cut in the
 * browser's canvas). As StudentPhotoController, for a teacher of the
 * signed-in admin's own school.
 */
class TeacherPhotoController extends StudentPhotoController
{
    protected string $role = 'teacher';
}
