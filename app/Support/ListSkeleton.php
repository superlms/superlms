<?php

namespace App\Support;

/**
 * The Super Admin lists' skeleton rows (partials.list-skeleton-rows): each
 * list's cells in order — 'sno', 'person' (a photo with a name and a line
 * under it), 'actions' (the status dot and the ⋮), or how much of its column
 * a bar may fill (in %). Shared by the page skeleton shown the moment a list's
 * link is clicked and the rows shown while the list itself reloads.
 */
final class ListSkeleton
{
    /** S.No, Student, Admission No, Father's Name, Mobile, Email, Gender, School. */
    public const STUDENTS = ['sno', 'person', 70, 72, 80, 85, 55, 80, 'actions'];

    /** S.No, Teacher, Username, Mobile, Email, Gender, Class Teacher, School. */
    public const TEACHERS = ['sno', 'person', 75, 80, 85, 55, 60, 80, 'actions'];

    /** S.No, School, Mobile, Board, School Code, Students, Teachers. */
    public const SCHOOLS = ['sno', 'person', 80, 70, 70, 45, 45, 'actions'];

    /** S.No, School, Board, Fee Type, Amount, Students, Collection. */
    public const FEES = ['sno', 'person', 60, 65, 60, 40, 90, 'actions'];
}
