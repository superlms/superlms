<?php

namespace App\Services\Gemini;

use App\Models\User;

/**
 * What the signed-in login is allowed to ask about.
 *
 * The assistant reads the same data the panel does, so it has to obey the same
 * permissions the panel does. A sub-admin who was never granted the Fee screen
 * cannot read fee rows here either, and the refusal has to say so — "you do not
 * have access to that screen" is a very different answer from "there is no
 * data", and reporting the second when the first is true is how an assistant
 * loses trust.
 *
 * Everything is expressed in MODULES — one per area of the product — because a
 * module is what both sides can agree on: screens are granted per route name,
 * while tools and record types are grouped per subject.
 */
class LmsAccess
{
    /** Every module the assistant knows about, and what to call it to a user. */
    public const MODULES = [
        'overview'      => 'Dashboard & analytics',
        'students'      => 'Students',
        'teachers'      => 'Teachers & staff',
        'classes'       => 'Classes, sections & subjects',
        'attendance'    => 'Attendance',
        'exams'         => 'Exams, marks & report cards',
        'fees'          => 'Fees',
        'ledger'        => 'Ledger',
        'payroll'       => 'Payroll & salaries',
        'transport'     => 'Transport',
        'homework'      => 'Homework & assignments',
        'syllabus'      => 'Syllabus & content',
        'timetable'     => 'Timetable & arrangements',
        'calendar'      => 'Calendar',
        'announcements' => 'Announcements',
        'library'       => 'Library',
        'enquiries'     => 'Enquiries',
        'certificates'  => 'Certificates & TC',
        'idcards'       => 'ID cards & admit cards',
        'users'         => 'Login accounts',
        'credit'        => 'Credit requests',
        'support'       => 'Support, policies & app info',
        'platform'      => 'Platform administration',
    ];

    /**
     * Modules nobody is refused: the school's own profile, the policies every
     * panel links to from More, and its own support thread with SuperLMS.
     */
    public const ALWAYS = ['overview', 'support'];

    /**
     * Admin screen (route name) → the modules it grants.
     *
     * This is the sub-admin permission model, which grants access one menu
     * entry at a time; the keys are exactly the links in `config/menu.php`.
     */
    private const ADMIN_ROUTES = [
        'admin.quick-links'   => ['overview'],
        'admin.home'          => ['overview'],
        'admin.analytics'     => ['overview'],
        'admin.standard'      => ['classes'],
        'admin.student'       => ['students', 'classes'],
        'admin.teacher'       => ['teachers'],
        'admin.users'         => ['users'],
        'admin.fee'           => ['fees'],
        'admin.ledger'        => ['ledger'],
        'admin.payroll'       => ['payroll', 'teachers'],
        'admin.attendance'    => ['attendance', 'students', 'teachers'],
        'admin.transport'     => ['transport'],
        'admin.homework'      => ['homework'],
        'admin.assignments'   => ['homework'],
        'admin.timetable'     => ['timetable', 'classes'],
        'admin.arrangement'   => ['timetable', 'teachers'],
        'admin.announcement'  => ['announcements'],
        'admin.calender'      => ['calendar'],
        'admin.syllabus'      => ['syllabus'],
        'admin.content'       => ['syllabus'],
        'admin.book'          => ['library'],
        'admin.enqueries'     => ['enquiries'],
        'admin.id-card'       => ['idcards', 'students'],
        'admin.lists'         => ['students', 'teachers', 'classes'],
        'admin.add-exam'      => ['exams'],
        'admin.admit-card'    => ['idcards', 'exams'],
        'admin.seating-plan'  => ['exams'],
        'admin.performance'   => ['exams'],
        'admin.exam-copy'     => ['exams'],
        'admin.report-card'   => ['exams'],
        'admin.tc-certificate' => ['certificates', 'students'],
        'admin.credit'        => ['credit'],
        'admin.more'          => ['support'],
    ];

    /**
     * What each role gets when it is not restricted screen-by-screen.
     *
     * A full admin owns the whole school panel. The accounts login owns the
     * money — and the handful of academic screens its own menu carries, so the
     * assistant does not refuse something the user can see two clicks away.
     */
    private const ROLE_MODULES = [
        'accounts' => [
            'overview', 'students', 'teachers', 'classes', 'attendance', 'exams',
            'fees', 'ledger', 'payroll', 'transport', 'calendar', 'credit',
            'idcards', 'certificates', 'support',
        ],
    ];

    /**
     * The modules this user may ask about.
     *
     * @return array<int,string>
     */
    public static function modulesFor(User $user): array
    {
        $role = (string) $user->role;

        // The platform panel, and a full school admin, read their whole side.
        if (in_array($role, ['super-admin', 'sub-super-admin'], true)) {
            return array_keys(self::MODULES);
        }

        if ($role === 'admin') {
            return array_values(array_diff(array_keys(self::MODULES), ['platform']));
        }

        if (isset(self::ROLE_MODULES[$role])) {
            return self::ROLE_MODULES[$role];
        }

        if ($role === 'sub-admin') {
            $modules = self::ALWAYS;

            foreach ((array) $user->permissions as $route) {
                // A granted screen also grants its sub-routes in the panel, so
                // match the same way: "admin.report-card.print" is Exams too.
                foreach (self::ADMIN_ROUTES as $name => $grants) {
                    if ($route === $name || str_starts_with((string) $route, $name . '.')) {
                        $modules = array_merge($modules, $grants);
                    }
                }
            }

            return array_values(array_unique($modules));
        }

        return self::ALWAYS;
    }

    /** "Fees", "Attendance" … — how a refusal names what is out of reach. */
    public static function label(string $module): string
    {
        return self::MODULES[$module] ?? $module;
    }

    /**
     * @param  array<int,string>  $modules
     * @return array<int,string>
     */
    public static function labels(array $modules): array
    {
        return array_values(array_unique(array_map([self::class, 'label'], $modules)));
    }
}
