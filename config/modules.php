<?php

/*
|--------------------------------------------------------------------------
| Toggleable School Modules
|--------------------------------------------------------------------------
| Each entry is a feature a super-admin can turn ON / OFF per school.
|
| 'links' lists the route names (admin + accounts panels) that belong to
| the module. These are used in two places:
|   1. Sidebar / Quick-Links / dashboard menu filtering (hide disabled).
|   2. The route guard middleware (App\Http\Middleware\EnsureModuleEnabled).
|
| IMPORTANT — backward compatibility:
|   * Any route NOT listed here is treated as a CORE feature and is ALWAYS
|     available (Home, Students, Teachers, Users, Analytics, policies, etc).
|   * A school with NO saved configuration gets every module at its
|     'default' (true) — so existing schools keep ALL features. Nothing is
|     hidden until a super-admin explicitly turns something off.
*/

return [
    'fees' => [
        'label'   => 'Fees',
        'default' => true,
        'links'   => [
            'admin.fee',
            'admin.fee-structure',
            'accounts.fee-submission',
            'accounts.view-fee',
            'accounts.fee-structure',
            'accounts.payments',
            'accounts.penalties',
            'accounts.fee-cycles',
        ],
    ],
    'payroll' => [
        'label'   => 'Payroll',
        'default' => true,
        'links'   => ['admin.payroll', 'accounts.payroll'],
    ],
    'credit' => [
        'label'   => 'Credit',
        'default' => true,
        'links'   => ['admin.credit', 'accounts.credit'],
    ],
    'attendance' => [
        'label'   => 'Attendance',
        'default' => true,
        'links'   => ['admin.attendance', 'accounts.attendance'],
    ],
    'transport' => [
        'label'   => 'Transportation',
        'default' => true,
        'links'   => ['admin.transport', 'accounts.transport'],
    ],
    'homework' => [
        'label'   => 'Homework',
        'default' => true,
        'links'   => ['admin.homework'],
    ],
    'timetable' => [
        'label'   => 'Time Table',
        'default' => true,
        'links'   => ['admin.timetable', 'admin.arrangement'],
    ],
    'announcement' => [
        'label'   => 'Announcement',
        'default' => true,
        'links'   => ['admin.announcement'],
    ],
    'calendar' => [
        'label'   => 'Calendar',
        'default' => true,
        'links'   => ['admin.calender', 'accounts.calendar'],
    ],
    'syllabus' => [
        'label'   => 'Syllabus',
        'default' => true,
        'links'   => ['admin.syllabus'],
    ],
    'content' => [
        'label'   => 'Content',
        'default' => true,
        'links'   => ['admin.content'],
    ],
    'quiz' => [
        'label'   => 'Quiz',
        'default' => true,
        'links'   => ['admin.quiz'],
    ],
    'book' => [
        'label'   => 'Book / Library',
        'default' => true,
        'links'   => ['admin.book'],
    ],
    'enquiries' => [
        'label'   => 'Enquiries',
        'default' => true,
        'links'   => ['admin.enqueries'],
    ],
    'id_card' => [
        'label'   => 'ID Card',
        'default' => true,
        'links'   => ['admin.id-card', 'accounts.id-card'],
    ],
    'exam' => [
        'label'   => 'Exam',
        'default' => true,
        'links'   => ['admin.add-exam', 'admin.seating-plan', 'admin.exam-copy'],
    ],
    'admit_card' => [
        'label'   => 'Admit Card',
        'default' => true,
        'links'   => ['admin.admit-card', 'accounts.admit-card'],
    ],
    'performance' => [
        'label'   => 'Performance',
        'default' => true,
        'links'   => ['admin.performance'],
    ],
    'report_card' => [
        'label'   => 'Report Card',
        'default' => true,
        'links'   => ['admin.report-card', 'accounts.report-card'],
    ],
    'tc_certificate' => [
        'label'   => 'TC & Certificate',
        'default' => true,
        'links'   => ['admin.tc-certificate', 'accounts.tc-certificate'],
    ],
    'admissions' => [
        'label'   => 'Admissions',
        'default' => true,
        'links'   => ['admin.admissions', 'accounts.admissions'],
    ],
];
