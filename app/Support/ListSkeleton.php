<?php

namespace App\Support;

/**
 * The Super Admin lists' skeleton rows (partials.list-skeleton-rows): each
 * list's cells in order — 'sno', 'person' (a photo with a name and a line
 * under it), 'actions' (the status dot and the ⋮), 'buttons' (the dot and
 * four icon buttons), 'arrow' (the dot and the › button), or how much of its
 * column a bar may fill (in %). Shared by the page skeleton shown when a
 * list's link is slow to open and the rows shown while the list reloads.
 */
final class ListSkeleton
{
    /**
     * How long a screen may take before its skeleton is shown, in ms: a
     * quicker answer shows the screen straight away. The same everywhere —
     * the page skeletons (partials.panel-ui), the rows and screens shown
     * through wire:loading (.delay.longer is Livewire's 500ms) and the
     * button spinner.
     */
    public const DELAY_MS = 500;

    /** S.No, Student, Admission No, Father's Name, Mobile, Email, Gender, School. */
    public const STUDENTS = ['sno', 'person', 70, 72, 80, 85, 55, 80, 'actions'];

    /** S.No, Teacher, Username, Mobile, Email, Gender, Class Teacher, School. */
    public const TEACHERS = ['sno', 'person', 75, 80, 85, 55, 60, 80, 'actions'];

    /** S.No, School, Mobile, Board, School Code, Students, Teachers, Actions. */
    public const SCHOOLS = ['sno', 'person', 80, 70, 70, 45, 45, 'buttons'];

    /** S.No, School, Board, Fee Type, Amount, Students, Collection, ›. */
    public const FEES = ['sno', 'person', 60, 65, 60, 40, 90, 'arrow'];

    /**
     * The list pages' skeletons (partials.list-page-skeleton), by route: the
     * title, how many filter boxes, whether the bar ends in Export, and the
     * table's real column names (with their header classes) over the rows.
     *
     * @return array<string, array{title: string, filters: int, export: bool, headers: list<array{0: string, 1: string}>, cols: list<int|string>}>
     */
    public static function pages(): array
    {
        $th  = 'px-3 py-2.5 text-left text-[11px] font-medium text-gray-500 truncate';
        $thR = 'px-3 py-2.5 text-right text-[11px] font-medium text-gray-500 truncate';
        $sno = 'pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500';
        $act = 'px-2 py-2.5 w-[60px]';

        return [
            'super-admin.students' => [
                'title'   => 'Students',
                'filters' => 5,
                'export'  => true,
                'headers' => [['S.No', $sno], ['Student', "$th w-[22%]"], ['Admission No', "$th w-[118px]"], ['Father\'s Name', $th],
                              ['Mobile', "$th w-[104px]"], ['Email', $th], ['Gender', "$th w-[78px]"], ['School', $th], ['', $act]],
                'cols'    => self::STUDENTS,
            ],
            'super-admin.teachers' => [
                'title'   => 'Teachers',
                'filters' => 3,
                'export'  => true,
                'headers' => [['S.No', $sno], ['Teacher', "$th w-[20%]"], ['Username', "$th w-[120px]"], ['Mobile', "$th w-[104px]"],
                              ['Email', $th], ['Gender', "$th w-[72px]"], ['Class Teacher', "$th w-[120px]"], ['School', $th], ['', $act]],
                'cols'    => self::TEACHERS,
            ],
            'super-admin.schools' => [
                'title'   => 'Schools',
                'filters' => 5,
                'export'  => false,
                'headers' => [['S.No', $sno], ['School', "$th w-[28%]"], ['Mobile', "$th w-[120px]"], ['Board', $th],
                              ['School Code', "$th w-[110px]"], ['Students', "$thR w-[90px]"], ['Teachers', "$thR w-[90px]"],
                              ['Actions', 'px-2 py-2.5 w-[168px] text-right text-[11px] font-medium text-gray-500 pr-4']],
                'cols'    => self::SCHOOLS,
            ],
            'super-admin.fees' => [
                'title'   => 'Fees',
                'filters' => 3,
                'export'  => false,
                'headers' => [['S.No', $sno], ['School', "$th w-[26%]"], ['Board', $th], ['Fee Type', "$th w-[110px]"],
                              ['Amount', "$th w-[130px]"], ['Students', "$th w-[80px]"], ['Collection', "$th w-[22%]"], ['', 'px-2 py-2.5 w-[76px]']],
                'cols'    => self::FEES,
            ],
        ];
    }

    /** One list page's skeleton, as pages() gives it. */
    public static function page(string $route): array
    {
        return self::pages()[$route];
    }
}
