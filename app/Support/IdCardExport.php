<?php

namespace App\Support;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\IdCardService;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * ID Card → Export: the data an ID-card printer needs, as a spreadsheet —
 * students, teachers, or management & staff (the Employees tab's people) —
 * with each person's photo in its own cell. The school picks the columns and
 * their order (first column, second…); what it picked is kept per school and
 * per kind, so the next export opens on it.
 *
 * Two formats: an .xlsx with the photos inside the sheet, or a .zip holding
 * that sheet and a photos/ folder — each photo named as the "Photo File Name"
 * column says — for card software that links photos by file name.
 *
 * Students can be one class (and a section of it), several classes, or the
 * whole school. A whole school's photos take minutes to fetch and fit — far
 * longer than one web request may run (nginx answers 504 at 60 s) — so the
 * file is built a few seconds at a time: start(), then step() until it says
 * ready, then file(). build() runs the same steps in one go.
 */
class IdCardExport
{
    public const TYPES = IdCardService::TYPES; // student | teacher | employee

    public const WHICH = ['all', 'issued', 'not_issued'];

    /** Photo cell: the picture's height in px, and the row's height in points. */
    private const THUMB_PX  = 96;
    private const ROW_PT    = 78;
    /** A photo in the zip's folder: its longer side at most this, in px. */
    private const FOLDER_PX = 800;

    /** A step fetches photos for about this many seconds, then answers. */
    private const STEP_SECONDS = 10;
    /** Photos fetched side by side. */
    private const BATCH = 16;
    /** A job left behind (the tab closed half-way) is cleared after this long, in seconds. */
    private const STALE_AFTER = 6 * 3600;

    /** Every column a kind can have, in the order "Add all" puts them: key => heading. */
    public static function fields(string $type): array
    {
        $card = [
            'card_number' => 'ID Card No',
            'card_issue'  => 'Card Issue Date',
            'card_expiry' => 'Card Valid Till',
            'session'     => 'Session',
        ];

        return match ($type) {
            'student' => [
                'sr_no'               => 'Sr No',
                'photo'               => 'Photo',
                'name'                => 'Name',
                'admission_no'        => 'Admission No',
                'roll_no'             => 'Roll No',
                'class'               => 'Class',
                'section'             => 'Section',
                'class_section'       => 'Class & Section',
                'father_name'         => "Father's Name",
                'mother_name'         => "Mother's Name",
                'dob'                 => 'Date of Birth',
                'gender'              => 'Gender',
                'phone'               => 'Mobile',
                'email'               => 'Email',
                'address'             => 'Address',
                'permanent_address'   => 'Permanent Address',
                'city'                => 'City',
                'state'               => 'State',
                'pincode'             => 'Pincode',
                'religion'            => 'Religion',
                'aadhar_no'           => 'Aadhaar No',
                'appar_id'            => 'APAAR ID',
                'pen_no'              => 'PEN No',
                'registration_number' => 'Registration No',
                'date_of_admission'   => 'Date of Admission',
                'board'               => 'Board',
                'bus_route'           => 'Bus Route',
                'bus_pickup'          => 'Pickup Point',
                'photo_file'          => 'Photo File Name',
            ] + $card,
            'teacher' => [
                'sr_no'             => 'Sr No',
                'photo'             => 'Photo',
                'name'              => 'Name',
                'employee_id'       => 'Employee ID',
                'designation'       => 'Designation',
                'qualification'     => 'Qualification',
                'phone'             => 'Mobile',
                'alt_phone'         => 'Alternate Mobile',
                'emergency_contact' => 'Emergency Contact',
                'email'             => 'Email',
                'dob'               => 'Date of Birth',
                'gender'            => 'Gender',
                'date_of_joining'   => 'Joining Date',
                'address'           => 'Address',
                'city'              => 'City',
                'state'             => 'State',
                'pincode'           => 'Pincode',
                'photo_file'        => 'Photo File Name',
            ] + $card,
            default => [
                'sr_no'        => 'Sr No',
                'photo'        => 'Photo',
                'name'         => 'Name',
                'employee_id'  => 'Employee ID',
                'designation'  => 'Designation',
                'staff_type'   => 'Type',
                'phone'        => 'Mobile',
                'email'        => 'Email',
                'joining_date' => 'Joining Date',
                'address'      => 'Address',
                'photo_file'   => 'Photo File Name',
            ] + $card,
        };
    }

    /**
     * The columns an export starts on (and Reset goes back to): what a card
     * prints — for a student Sr No, Name, Class "V (A)", Father's and Mother's
     * Name, Date of Birth, Address, Mobile and the Photo, in that order.
     */
    public static function defaults(string $type): array
    {
        return match ($type) {
            'student' => ['sr_no', 'name', 'class', 'father_name', 'mother_name', 'dob', 'address', 'phone', 'photo'],
            'teacher' => ['sr_no', 'name', 'designation', 'dob', 'address', 'phone', 'photo'],
            default   => ['sr_no', 'name', 'designation', 'address', 'phone', 'photo'],
        };
    }

    /** The kind's name as the sheet and the file name carry it. */
    public static function label(string $type): string
    {
        return match ($type) {
            'student' => 'Students',
            'teacher' => 'Teachers',
            default   => 'Management & Staff',
        };
    }

    /** Only the known columns, each once, in the order given. */
    public static function clean(string $type, array $columns): array
    {
        $known = self::fields($type);

        return array_values(array_unique(array_filter($columns, fn ($c) => is_string($c) && isset($known[$c]))));
    }

    // ── What the school picked, kept for the next export ────────────────────

    private static function prefKey(int $orgId, string $type): string
    {
        // v2: every school starts again on the card's own columns (defaults()).
        return "id-card-export:v2:{$orgId}:{$type}";
    }

    public static function savedColumns(int $orgId, string $type): array
    {
        $saved = self::clean($type, (array) cache()->get(self::prefKey($orgId, $type), []));

        return $saved ?: self::defaults($type);
    }

    public static function saveColumns(int $orgId, string $type, array $columns): void
    {
        cache()->forever(self::prefKey($orgId, $type), self::clean($type, $columns));
    }

    // ── The people ──────────────────────────────────────────────────────────

    /**
     * The classes picked, as ids: one class id, several (an array, or "3,5,8"),
     * or none — every class.
     *
     * @return int[]
     */
    public static function standardIds(mixed $standards): array
    {
        if (is_string($standards)) {
            $standards = explode(',', $standards);
        }

        return array_values(array_unique(array_filter(
            array_map('intval', (array) $standards),
            fn ($id) => $id > 0,
        )));
    }

    /**
     * The kind's people in the school — students of the classes picked (every
     * class when none; a section only when one class is picked) in class
     * order, A to Z within it; teachers and staff A to Z — narrowed to those
     * holding an active ID card (issued) or holding none (not_issued).
     *
     * @param  int|string|int[]|null  $standards  see standardIds()
     */
    public static function people(int $orgId, string $type, mixed $standards = null, ?int $sectionId = null, string $which = 'all', ?int $limit = null): Collection
    {
        $query = self::query($orgId, $type, $standards, $sectionId, $which);

        if ($type === 'student') {
            // Class order, then section, then A to Z — worked out on the bare
            // rows, so a preview of three loads only those three in full.
            $position = Standard::where('organization_id', $orgId)->inClassOrder()->pluck('id')->flip();
            $ids = (clone $query)->get(['id', 'standard_id', 'section_id', 'full_name'])
                ->sortBy(fn ($s) => sprintf('%05d|%010d|%s|%010d', $position[$s->standard_id] ?? 99999, (int) $s->section_id, mb_strtolower((string) $s->full_name), $s->id))
                ->pluck('id');
            if ($limit) {
                $ids = $ids->take($limit);
            }
            $byId = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name', 'transportations'])
                ->whereIn('id', $ids)->get()->keyBy('id');

            return $ids->map(fn ($id) => $byId->get($id))->filter()->values();
        }

        $people = $type === 'teacher'
            ? $query->with('user')->get()->sortBy(fn ($t) => mb_strtolower((string) ($t->user->name ?? '')))->values()
            : $query->orderBy('name')->orderBy('id')->get();

        return $limit ? $people->take($limit)->values() : $people;
    }

    /** How many people the export will have. */
    public static function count(int $orgId, string $type, mixed $standards = null, ?int $sectionId = null, string $which = 'all'): int
    {
        return self::query($orgId, $type, $standards, $sectionId, $which)->count();
    }

    /** The kind's people, narrowed by classes / section and by holding a card — unordered. */
    private static function query(int $orgId, string $type, mixed $standards, ?int $sectionId, string $which)
    {
        if ($type === 'student') {
            $classes = self::standardIds($standards);
            $query = StudentDetail::where('organization_id', $orgId)
                ->when($classes, fn ($q) => $q->whereIn('standard_id', $classes))
                // A section belongs to one class: it narrows only when one class is picked.
                ->when(count($classes) === 1 && $sectionId, fn ($q) => $q->where('section_id', $sectionId));
            $cards = StudentIdCard::where('organization_id', $orgId)->where('status', 'active')->select('student_detail_id');
        } elseif ($type === 'teacher') {
            $query = TeacherDetail::where('organization_id', $orgId);
            $cards = TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')->select('teacher_detail_id');
        } else {
            // The Employees tab's people: management, employees and drivers — not teachers.
            $query = AdminEmployee::where('organization_id', $orgId)->where('type', '!=', 'teacher');
            $cards = EmployeeIdCard::where('organization_id', $orgId)->where('status', 'active')->select('admin_employee_id');
        }

        if ($which === 'issued') {
            $query->whereIn('id', $cards);
        } elseif ($which === 'not_issued') {
            $query->whereNotIn('id', $cards);
        }

        return $query;
    }

    /** [person id => their active card] (the latest, should there be two). */
    private static function cards(int $orgId, string $type, Collection $people): Collection
    {
        [$model, $fk] = match ($type) {
            'student' => [StudentIdCard::class, 'student_detail_id'],
            'teacher' => [TeacherIdCard::class, 'teacher_detail_id'],
            default   => [EmployeeIdCard::class, 'admin_employee_id'],
        };

        return $model::where('organization_id', $orgId)->where('status', 'active')
            ->whereIn($fk, $people->pluck('id'))
            ->orderBy('id')->get()
            ->keyBy($fk);
    }

    /** The stored photo (a URL, or a path on the public disk), as a link. */
    public static function photoUrl($person, string $type): ?string
    {
        $value = match ($type) {
            'student' => $person->image ?: ($person->user->image ?? null),
            'teacher' => $person->user->image ?? null,
            default   => $person->photo ?? null,
        };

        $url = app(IdCardService::class)->resolvePhoto($value ?: null);
        if ($url && str_starts_with($url, '/')) {
            $url = url($url);
        }

        return $url ?: null;
    }

    /**
     * The class, then the section's last letter or digit in brackets — "V" and
     * "Section A" make "V (A)"; a student with no section keeps the bare class.
     */
    public static function classWithSection(string $class, string $section): string
    {
        $class = trim($class);
        if (!preg_match('/([\p{L}\p{N}])[^\p{L}\p{N}]*$/u', trim($section), $m)) {
            return $class;
        }

        return trim($class . ' (' . mb_strtoupper($m[1]) . ')');
    }

    /** The name a person's photo goes by in the zip — their admission number or employee ID. */
    private static function photoBase($person, string $type): string
    {
        $raw = match ($type) {
            'student' => $person->admission_no ?: 'student-' . $person->id,
            'teacher' => $person->employee_id ?: 'teacher-' . $person->id,
            default   => 'EMP-' . $person->id,
        };

        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $raw), '-') ?: ($type . '-' . $person->id);
    }

    /**
     * One row of values for each person, keyed by column: what the sheet (and
     * the panel's preview) shows. The photo column's value is the photo's link.
     *
     * @return array<int, array<string, string>>
     */
    public static function rows(int $orgId, string $type, Collection $people, array $columns): array
    {
        $cards   = self::cards($orgId, $type, $people);
        $session = AcademicYear::label();
        $date    = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '';
        $names   = [];
        $rows    = [];

        foreach ($people->values() as $i => $p) {
            $card = $cards->get($p->id);

            // Photo file names stay unique: a repeated one gets -2, -3…
            $base = self::photoBase($p, $type);
            $n    = $names[strtolower($base)] = ($names[strtolower($base)] ?? 0) + 1;
            $file = ($n > 1 ? $base . '-' . $n : $base) . '.jpg';

            $common = [
                'sr_no'       => (string) ($i + 1),
                'photo'       => (string) self::photoUrl($p, $type),
                'photo_file'  => self::photoUrl($p, $type) ? $file : '',
                'card_number' => (string) ($card->card_number ?? ''),
                'card_issue'  => $date($card->issue_date ?? null),
                'card_expiry' => $date($card->expiry_date ?? null),
                'session'     => $session,
            ];

            if ($type === 'student') {
                $route = $p->transportations->firstWhere('is_active', true);
                $class = $p->standard->name ?? '';
                $sec   = $p->section->name ?? '';
                $values = $common + [
                    'name'                => (string) ($p->full_name ?: ($p->user->name ?? '')),
                    'admission_no'        => (string) $p->admission_no,
                    'roll_no'             => (string) $p->roll_no,
                    // "V (A)": the class with its section's last letter, as a card prints it.
                    'class'               => self::classWithSection($class, $sec),
                    'section'             => $sec,
                    'class_section'       => trim($class . ($sec !== '' ? ' - ' . $sec : '')),
                    'father_name'         => (string) $p->father_name,
                    'mother_name'         => (string) $p->mother_name,
                    'dob'                 => $date($p->dob),
                    'gender'              => ucfirst((string) $p->gender),
                    'phone'               => (string) $p->phone,
                    'email'               => (string) $p->email,
                    'address'             => (string) ($p->local_address ?: $p->permanent_address),
                    'permanent_address'   => (string) $p->permanent_address,
                    'city'                => (string) $p->city,
                    'state'               => (string) $p->state,
                    'pincode'             => (string) $p->pincode,
                    'religion'            => (string) $p->religion,
                    'aadhar_no'           => (string) $p->aadhar_no,
                    'appar_id'            => (string) $p->appar_id,
                    'pen_no'              => (string) $p->pen_no,
                    'registration_number' => (string) $p->registration_number,
                    'date_of_admission'   => $date($p->date_of_admission),
                    'board'               => (string) $p->board,
                    'bus_route'           => (string) ($route->route_name ?? ''),
                    'bus_pickup'          => (string) ($route->pickup_location ?? ''),
                ];
            } elseif ($type === 'teacher') {
                $u = $p->user;
                $values = $common + [
                    'name'              => (string) ($u->name ?? ''),
                    'employee_id'       => (string) $p->employee_id,
                    'designation'       => 'Teacher',
                    'qualification'     => (string) $p->qualification,
                    'phone'             => (string) ($p->phone ?: ($u->mobile_number ?? '')),
                    'alt_phone'         => (string) ($u->alternative_mobile ?? ''),
                    'emergency_contact' => (string) $p->emergency_contact,
                    'email'             => (string) ($u->email ?? ''),
                    'dob'               => $date($u->dob ?? null),
                    'gender'            => ucfirst((string) ($u->gender ?? '')),
                    'date_of_joining'   => $date($p->date_of_joining ?: ($u->date_of_joining ?? null)),
                    'address'           => (string) ($p->address ?: ($u->address ?? '')),
                    'city'              => (string) $p->city,
                    'state'             => (string) $p->state,
                    'pincode'           => (string) $p->pincode,
                ];
            } else {
                $values = $common + [
                    'name'         => (string) $p->name,
                    'employee_id'  => 'EMP-' . $p->id,
                    'designation'  => (string) $p->designation,
                    'staff_type'   => implode(', ', array_map('ucfirst', method_exists($p, 'types') ? $p->types() : [(string) $p->type])),
                    'phone'        => (string) $p->mobile,
                    'email'        => (string) $p->email,
                    'joining_date' => $date($p->joining_date),
                    'address'      => (string) $p->address,
                ];
            }

            $rows[] = array_map(fn ($c) => (string) ($values[$c] ?? ''), array_combine($columns, $columns));
        }

        return $rows;
    }

    // ── The file ────────────────────────────────────────────────────────────

    /**
     * Build the export in one go and return [file path, download name, mime
     * type, working folder]. The caller sends the file, then hands the folder
     * to cleanup(). Fine for a class; the panel uses start() / step() instead,
     * so a whole school never runs into the gateway's time limit.
     */
    public static function build(int $orgId, string $schoolName, string $type, array $columns, mixed $standards, ?int $sectionId, string $which, string $format, int $userId = 0): array
    {
        $job = self::start($orgId, $userId, $schoolName, $type, $columns, $standards, $sectionId, $which, $format);
        do {
            $status = self::step($job['id'], $orgId, $userId, null);
        } while ($status && !$status['ready']);

        return self::file($job['id'], $orgId, $userId);
    }

    /**
     * Begin an export: who is in it, their rows, and which photos to fetch —
     * kept in a working folder of its own. Quick: no photo is fetched yet.
     *
     * @return array{id: string, people: int, total: int}  total = photos to fetch
     */
    public static function start(int $orgId, int $userId, string $schoolName, string $type, array $columns, mixed $standards, ?int $sectionId, string $which, string $format): array
    {
        self::clearStale();

        $format  = $format === 'zip' ? 'zip' : 'xlsx';
        $columns = self::clean($type, $columns) ?: self::defaults($type);
        $people  = self::people($orgId, $type, $standards, $sectionId, $which);
        $thumbs  = in_array('photo', $columns, true);
        $full    = $format === 'zip';

        // The photo's link and its name in the zip come with the row.
        $all = self::rows($orgId, $type, $people, array_values(array_unique([...$columns, 'photo', 'photo_file'])));

        $rows = $photos = $files = [];
        foreach ($all as $i => $r) {
            $rows[$i] = array_combine($columns, array_map(fn ($c) => $r[$c], $columns));
            if (($thumbs || $full) && $r['photo'] !== '') {
                $photos[$i] = $r['photo'];
                $files[$i]  = $r['photo_file'];
            }
        }

        $id   = Str::random(24);
        $work = self::workDir($id);
        @mkdir($work, 0775, true);

        self::save($work, [
            'org'     => $orgId,
            'user'    => $userId,
            'type'    => $type,
            'columns' => $columns,
            'rows'    => $rows,
            'photos'  => $photos,   // row index => link
            'files'   => $files,    // row index => name in the zip's photos/ folder
            'thumbs'  => $thumbs,
            'full'    => $full,
            'format'  => $format,
            'stem'    => Str::slug($schoolName ?: 'school') . '_' . Str::slug(self::label($type)) . '_'
                . self::scope($orgId, $type, $standards, $sectionId) . '_' . now()->format('Y-m-d'),
            'next'    => 0,         // photos fetched so far
            'ready'   => false,
        ]);

        return ['id' => $id, 'people' => count($rows), 'total' => count($photos)];
    }

    /**
     * One step of an export: fetch photos for about $seconds (at least one
     * batch; null = all of them), or — once every photo is in — write the
     * sheet (and the zip). Null when there is no such export for this person.
     *
     * @return array{done: int, total: int, stage: string, ready: bool, busy?: bool}|null
     */
    public static function step(string $id, int $orgId, int $userId, ?float $seconds = self::STEP_SECONDS): ?array
    {
        $work = self::workDir($id);
        $job  = $work ? self::load($work) : null;
        if (!$job || $job['org'] !== $orgId || $job['user'] !== $userId) {
            return null;
        }

        // One step at a time: a second one only says how far the first has got.
        // The lock lasts no longer than php-fpm lets a request run (120 s).
        $lock = Cache::lock('id-card-export-step:' . $id, 120);
        if (!$lock->get()) {
            return self::status($job) + ['busy' => true];
        }

        try {
            $job = self::load($work) ?? $job;
            if ($job['ready']) {
                return self::status($job);
            }

            $keys    = array_keys($job['photos']);
            $total   = count($keys);
            $fetched = false;
            $started = microtime(true);
            while ($job['next'] < $total && (!$fetched || $seconds === null || microtime(true) - $started < $seconds)) {
                $batch = array_slice($keys, $job['next'], self::BATCH);
                self::fetchPhotos(array_intersect_key($job['photos'], array_flip($batch)), $work, $job['thumbs'], $job['full']);
                $job['next'] += count($batch);
                $fetched = true;
                self::save($work, $job);
            }

            // The sheet gets a step of its own, unless it is all in one go.
            if ($job['next'] >= $total && ($seconds === null || !$fetched)) {
                self::finish($work, $job);
                self::save($work, $job);
            }

            return self::status($job);
        } finally {
            $lock->release();
        }
    }

    /**
     * The finished file: [path, download name, mime type, working folder], or
     * null when it is not ready or not this person's.
     */
    public static function file(string $id, int $orgId, int $userId): ?array
    {
        $work = self::workDir($id);
        $job  = $work ? self::load($work) : null;
        if (!$job || !$job['ready'] || $job['org'] !== $orgId || $job['user'] !== $userId) {
            return null;
        }

        $path = $work . '/' . $job['result']['file'];

        return is_file($path) ? [$path, $job['result']['name'], $job['result']['mime'], $work] : null;
    }

    /** Delete an export's working folder. */
    public static function cleanup(string $work): void
    {
        foreach (glob($work . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($work);
    }

    /** How far an export has got, as the panel shows it. */
    private static function status(array $job): array
    {
        $total = count($job['photos']);

        return [
            'done'  => min($job['next'], $total),
            'total' => $total,
            'stage' => $job['ready'] ? 'ready' : ($job['next'] < $total ? 'photos' : 'sheet'),
            'ready' => (bool) $job['ready'],
        ];
    }

    /** Write the sheet (and, for the zip, put it and the photos together); the job is then ready. */
    private static function finish(string $work, array &$job): void
    {
        $photos = [];
        foreach (array_keys($job['photos']) as $i) {
            $thumb = "{$work}/t{$i}.jpg";
            $photos[$i] = ['thumb' => is_file($thumb) ? $thumb : null];
        }

        $sheetPath = $work . '/sheet.xlsx';
        self::writeSheet($sheetPath, $job['type'], $job['columns'], $job['rows'], $photos);

        if ($job['format'] !== 'zip') {
            $job['result'] = ['file' => 'sheet.xlsx', 'name' => $job['stem'] . '.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        } else {
            $zip = new ZipArchive();
            $zip->open($work . '/export.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($sheetPath, $job['stem'] . '.xlsx');
            foreach ($job['files'] as $i => $name) {
                $full = "{$work}/f{$i}.jpg";
                if ($name !== '' && is_file($full)) {
                    $zip->addFile($full, 'photos/' . $name);
                    // A JPEG does not squeeze any smaller: stored as it is, the zip is made in a moment.
                    $zip->setCompressionName('photos/' . $name, ZipArchive::CM_STORE);
                }
            }
            $zip->close();
            $job['result'] = ['file' => 'export.zip', 'name' => $job['stem'] . '.zip', 'mime' => 'application/zip'];
        }

        $job['ready'] = true;
    }

    /** The file name's middle: "all", the one class "v-a", or the classes picked "v-vi-vii" (or "8-classes"). */
    private static function scope(int $orgId, string $type, mixed $standards, ?int $sectionId): string
    {
        $classes = self::standardIds($standards);
        if ($type !== 'student' || !$classes) {
            return 'all';
        }

        if (count($classes) === 1) {
            return Str::slug(self::classWithSection(
                (string) Standard::whereKey($classes[0])->value('name'),
                $sectionId ? (string) Section::whereKey($sectionId)->value('name') : '',
            )) ?: 'class';
        }

        $names = Standard::where('organization_id', $orgId)->whereIn('id', $classes)->inClassOrder()->pluck('name');

        return count($classes) <= 4 ? (Str::slug($names->implode(' ')) ?: count($classes) . '-classes') : count($classes) . '-classes';
    }

    /** An export's working folder; null for an id that is not one of ours. */
    private static function workDir(string $id): ?string
    {
        return preg_match('/^[A-Za-z0-9]{24}$/', $id) ? storage_path('app/tmp/id-card-export-' . $id) : null;
    }

    private static function load(string $work): ?array
    {
        $raw = @file_get_contents($work . '/job.json');
        $job = $raw ? json_decode($raw, true) : null;

        return is_array($job) ? $job : null;
    }

    private static function save(string $work, array $job): void
    {
        $tmp = $work . '/job.json.' . Str::random(6);
        file_put_contents($tmp, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
        rename($tmp, $work . '/job.json');
    }

    /** Clear what exports left behind (a tab closed half-way, a file never fetched). */
    private static function clearStale(): void
    {
        foreach (glob(storage_path('app/tmp/id-card-export-*'), GLOB_ONLYDIR) ?: [] as $dir) {
            if ((int) @filemtime($dir) < time() - self::STALE_AFTER) {
                self::cleanup($dir);
            }
        }
    }

    /**
     * Fetch these photos side by side and keep, for each, a small JPEG for the
     * sheet (t{i}.jpg) and, for the zip, a larger one (f{i}.jpg).
     *
     * @param  array<int, string>  $urls  row index => link
     */
    private static function fetchPhotos(array $urls, string $work, bool $thumbs, bool $full): void
    {
        $bytes = [];
        $http  = array_filter($urls, fn ($u) => !str_starts_with($u, 'data:'));
        foreach (array_diff_key($urls, $http) as $i => $u) {
            $bytes[$i] = base64_decode((string) substr($u, (int) strpos($u, ',') + 1)) ?: null;
        }

        if ($http) {
            try {
                $responses = Http::pool(fn (Pool $pool) => array_map(
                    fn ($i, $u) => $pool->as((string) $i)->timeout(15)->connectTimeout(5)->get($u),
                    array_keys($http),
                    $http,
                ));
            } catch (\Throwable $e) {
                $responses = [];
            }
            foreach ($http as $i => $u) {
                $r = $responses[(string) $i] ?? null;
                $bytes[$i] = ($r instanceof \Illuminate\Http\Client\Response && $r->successful()) ? $r->body() : null;
            }
        }

        foreach ($bytes as $i => $data) {
            $src = $data ? self::decode($data) : null;
            unset($bytes[$i]);
            if (!$src) {
                continue;
            }
            // Decoded once, fitted to both sizes.
            if ($thumbs) {
                self::jpeg($src, self::THUMB_PX * 2, "{$work}/t{$i}.jpg", true);
            }
            if ($full) {
                self::jpeg($src, self::FOLDER_PX, "{$work}/f{$i}.jpg", false);
            }
            imagedestroy($src);
        }
    }

    /** The picture, upright; null when it is not an image. */
    private static function decode(string $data): ?\GdImage
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if (!$src) {
            return null;
        }

        // A phone photo is stored as the camera held it and only says how to
        // turn it; a browser turns it, GD does not — the sheet showed it on its side.
        return PhotoOrientation::upright($src, PhotoOrientation::of($data));
    }

    /** The picture as a JPEG at most $max px on its longer side (or $max tall). */
    private static function jpeg(\GdImage $src, int $max, string $path, bool $byHeight): ?string
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = $byHeight ? min(1, $max / max(1, $h)) : min(1, $max / max(1, max($w, $h)));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        // A transparent PNG sits on white, not black.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = imagejpeg($dst, $path, 88);
        imagedestroy($dst);

        return $ok ? $path : null;
    }

    private static function writeSheet(string $path, string $type, array $columns, array $rows, array $photos): void
    {
        $fields = self::fields($type);
        $book   = new Spreadsheet();
        $sheet  = $book->getActiveSheet();
        $sheet->setTitle(Str::limit(self::label($type), 31, ''));

        $last = Coordinate::stringFromColumnIndex(max(1, count($columns)));

        // Row 1: the headings.
        foreach ($columns as $c => $key) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($c + 1) . '1', $fields[$key], DataType::TYPE_STRING);
        }
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $photoCol = array_search('photo', $columns, true);
        $widths   = array_map(fn ($key) => mb_strlen($fields[$key]), $columns);

        foreach ($rows as $r => $row) {
            $line = $r + 2;
            foreach ($columns as $c => $key) {
                $cell = Coordinate::stringFromColumnIndex($c + 1) . $line;
                if ($key === 'photo') {
                    continue; // the picture goes over this cell below
                }
                // Text, always: an admission number, Aadhaar or phone keeps its leading zeros.
                $sheet->setCellValueExplicit($cell, $row[$key], DataType::TYPE_STRING);
                $widths[$c] = max($widths[$c], min(50, mb_strlen($row[$key])));
            }

            if ($photoCol !== false) {
                $sheet->getRowDimension($line)->setRowHeight(self::ROW_PT);
                $thumb = $photos[$r]['thumb'] ?? null;
                if ($thumb && is_file($thumb)) {
                    $drawing = new Drawing();
                    $drawing->setName('Photo ' . ($r + 1));
                    $drawing->setPath($thumb);
                    $drawing->setHeight(self::THUMB_PX);
                    // A wide photo is fitted to the cell's width instead.
                    if ($drawing->getWidth() > 112) {
                        $drawing->setWidth(112);
                    }
                    $drawing->setCoordinates(Coordinate::stringFromColumnIndex($photoCol + 1) . $line);
                    $drawing->setOffsetX(6);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }
            }
        }

        foreach ($columns as $c => $key) {
            $letter = Coordinate::stringFromColumnIndex($c + 1);
            $sheet->getColumnDimension($letter)->setWidth($key === 'photo' ? 14 : max(8, $widths[$c] + 2));
        }

        $sheet->getStyle("A2:{$last}" . (count($rows) + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        if ($rows) {
            $sheet->setAutoFilter("A1:{$last}" . (count($rows) + 1));
        }

        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
    }
}
