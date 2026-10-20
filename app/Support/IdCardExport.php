<?php

namespace App\Support;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\IdCardService;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
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

    /** The columns an export starts on before the school has picked its own. */
    public static function defaults(string $type): array
    {
        return match ($type) {
            'student' => ['sr_no', 'photo', 'name', 'admission_no', 'class', 'section', 'roll_no', 'father_name', 'dob', 'phone', 'address'],
            'teacher' => ['sr_no', 'photo', 'name', 'employee_id', 'designation', 'phone', 'email', 'dob', 'address'],
            default   => ['sr_no', 'photo', 'name', 'employee_id', 'designation', 'phone', 'address'],
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
        return "id-card-export:{$orgId}:{$type}";
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
     * The kind's people in the school — students of the class / section picked
     * (every class when none) in class order, A to Z within it; teachers and
     * staff A to Z — narrowed to those holding an active ID card (issued) or
     * holding none (not_issued).
     */
    public static function people(int $orgId, string $type, ?int $standardId = null, ?int $sectionId = null, string $which = 'all', ?int $limit = null): Collection
    {
        $query = self::query($orgId, $type, $standardId, $sectionId, $which);

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
    public static function count(int $orgId, string $type, ?int $standardId = null, ?int $sectionId = null, string $which = 'all'): int
    {
        return self::query($orgId, $type, $standardId, $sectionId, $which)->count();
    }

    /** The kind's people, narrowed by class / section and by holding a card — unordered. */
    private static function query(int $orgId, string $type, ?int $standardId, ?int $sectionId, string $which)
    {
        if ($type === 'student') {
            $query = StudentDetail::where('organization_id', $orgId)
                ->when($standardId, fn ($q) => $q->where('standard_id', $standardId))
                ->when($standardId && $sectionId, fn ($q) => $q->where('section_id', $sectionId));
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
                    'class'               => $class,
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
     * Build the export into a working folder and return [file path, download
     * name, mime type, working folder]. The caller sends the file, then hands
     * the folder to cleanup().
     */
    public static function build(int $orgId, string $schoolName, string $type, array $columns, ?int $standardId, ?int $sectionId, string $which, string $format): array
    {
        $columns = self::clean($type, $columns) ?: self::defaults($type);
        $people  = self::people($orgId, $type, $standardId, $sectionId, $which);
        $rows    = self::rows($orgId, $type, $people, $columns);

        $withPhotos = in_array('photo', $columns, true) || $format === 'zip';
        $work       = storage_path('app/tmp/id-card-export-' . Str::random(16));
        @mkdir($work, 0775, true);

        // Every photo fetched once, side by side; [row index => [thumb, full]] files.
        $photos = $withPhotos ? self::fetchPhotos($people, $type, $work, $format === 'zip') : [];

        $sheetPath = $work . '/sheet.xlsx';
        self::writeSheet($sheetPath, $type, $columns, $rows, $photos);

        $scope = $type === 'student' && $standardId
            ? (Str::slug((string) Standard::whereKey($standardId)->value('name')) ?: 'class')
            : 'all';
        $stem = Str::slug($schoolName ?: 'school') . '_' . Str::slug(self::label($type)) . '_' . $scope . '_' . now()->format('Y-m-d');

        if ($format !== 'zip') {
            return [$sheetPath, $stem . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $work];
        }

        $zipPath = $work . '/export.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($sheetPath, $stem . '.xlsx');
        $fileCol = 'photo_file';
        $fileNames = self::rows($orgId, $type, $people, [$fileCol]);
        foreach ($photos as $i => $p) {
            $name = $fileNames[$i][$fileCol] ?? '';
            if ($name !== '' && !empty($p['full']) && is_file($p['full'])) {
                $zip->addFile($p['full'], 'photos/' . $name);
            }
        }
        $zip->close();

        return [$zipPath, $stem . '.zip', 'application/zip', $work];
    }

    /** Delete what build() left in its working folder. */
    public static function cleanup(string $work): void
    {
        foreach (glob($work . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($work);
    }

    /**
     * Fetch every person's photo, twenty at a time in parallel, and keep a
     * small JPEG for the sheet (and, for the zip, a larger one).
     *
     * @return array<int, array{thumb: ?string, full: ?string}>  by row index
     */
    private static function fetchPhotos(Collection $people, string $type, string $work, bool $full): array
    {
        $urls = [];
        foreach ($people->values() as $i => $p) {
            if ($url = self::photoUrl($p, $type)) {
                $urls[$i] = $url;
            }
        }

        $out = [];
        foreach (array_chunk($urls, 20, true) as $chunk) {
            $bytes = [];
            $http  = array_filter($chunk, fn ($u) => !str_starts_with($u, 'data:'));
            foreach (array_diff_key($chunk, $http) as $i => $u) {
                $bytes[$i] = base64_decode((string) substr($u, (int) strpos($u, ',') + 1)) ?: null;
            }

            if ($http) {
                try {
                    $responses = Http::pool(fn (Pool $pool) => array_map(
                        fn ($i, $u) => $pool->as((string) $i)->timeout(20)->connectTimeout(5)->get($u),
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
                $out[$i] = [
                    'thumb' => $data ? self::jpeg($data, self::THUMB_PX * 2, "{$work}/t{$i}.jpg", true) : null,
                    'full'  => $data && $full ? self::jpeg($data, self::FOLDER_PX, "{$work}/f{$i}.jpg", false) : null,
                ];
            }
        }

        return $out;
    }

    /** The image as a JPEG at most $max px on its longer side (or $max tall); null when it is not an image. */
    private static function jpeg(string $data, int $max, string $path, bool $byHeight): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if (!$src) {
            return null;
        }

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
        imagedestroy($src);
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
