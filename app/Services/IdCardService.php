<?php

namespace App\Services;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Centralised ID-card generation used by both the admin Livewire screen and the
 * scheduled `id-cards:generate-missing` command. Generation is always "fill the
 * gaps": only persons of the given type who do NOT already have an active card
 * get one, so it is safe to run repeatedly.
 */
class IdCardService
{
    public const TYPES = ['student', 'teacher', 'employee'];

    /**
     * How long a Generate click (panel or app) spends drawing QR codes. A QR
     * takes a few hundred ms, so a whole school's would outlast nginx (a 504);
     * cards made after this get their QR when first opened — view, print and
     * both apps draw a missing one — or from the nightly id-cards command.
     */
    public const QR_SECONDS_PER_REQUEST = 15;

    /**
     * Generate cards for every person of $type (without an active card) in the
     * organization. For students an optional list of standard (class) ids
     * narrows the batch. With $qrSeconds, QR codes are drawn only for that long;
     * every card is still created.
     *
     * @return array{generated:int, skipped:int, errors:array<int,string>}
     */
    public function generateForType(Organization $organization, string $type, string $expiryDate, ?array $standardIds = null, ?int $userId = null, ?int $qrSeconds = null): array
    {
        $persons = $this->personsWithoutActiveCard($organization, $type, $standardIds);

        $generated = 0;
        $errors = [];
        $qrUntil = $qrSeconds !== null ? microtime(true) + $qrSeconds : null;

        foreach ($persons as $person) {
            try {
                $card = $this->createCardFor($organization, $type, $person, $expiryDate, $userId);

                if ($qrUntil === null || microtime(true) < $qrUntil) {
                    $qr = $this->generateQrCode($card, $person, $organization, $type);
                    if ($qr) {
                        $card->update(['qr_code' => $qr]);
                    }
                }

                $generated++;
            } catch (\Throwable $e) {
                $errors[] = $this->personName($person, $type) . ': ' . $e->getMessage();
                Log::error("ID Card generation failed ({$type}): " . $e->getMessage());
            }
        }

        return [
            'generated' => $generated,
            'skipped'   => 0,
            'errors'    => $errors,
        ];
    }

    /**
     * Draw the QR of every active card of $type that has none — cards a
     * Generate click made after its QR time ran out. Run by the nightly
     * id-cards command, which has no time limit.
     */
    public function fillMissingQrCodes(Organization $organization, string $type): int
    {
        $model = $this->modelClassFor($type);
        $with = match ($type) {
            'student' => ['studentDetail.standard', 'studentDetail.section'],
            'teacher' => ['teacherDetail.user'],
            default   => ['adminEmployee'],
        };
        $personOf = fn ($card) => match ($type) {
            'student' => $card->studentDetail,
            'teacher' => $card->teacherDetail,
            default   => $card->adminEmployee,
        };

        $filled = 0;
        $model::with($with)
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->whereNull('qr_code')
            ->chunkById(100, function ($cards) use ($organization, $type, $personOf, &$filled) {
                foreach ($cards as $card) {
                    $person = $personOf($card);
                    if (!$person) {
                        continue;
                    }
                    $qr = $this->generateQrCode($card, $person, $organization, $type);
                    if ($qr) {
                        $card->update(['qr_code' => $qr]);
                        $filled++;
                    }
                }
            });

        return $filled;
    }

    /**
     * Persons of the given type that don't yet have an active ID card.
     */
    public function personsWithoutActiveCard(Organization $organization, string $type, ?array $standardIds = null)
    {
        $active = fn ($q) => $q->where('status', 'active');

        if ($type === 'student') {
            $query = StudentDetail::with(['standard', 'section', 'user'])
                ->where('organization_id', $organization->id)
                ->whereDoesntHave('idCards', $active);

            if (!empty($standardIds)) {
                $query->whereIn('standard_id', $standardIds);
            }

            return $query->get();
        }

        if ($type === 'teacher') {
            return TeacherDetail::with(['user', 'assignedClasses.standard', 'assignedClasses.section'])
                ->where('organization_id', $organization->id)
                ->whereDoesntHave('idCards', $active)
                ->get();
        }

        // employee — all admin employees
        return AdminEmployee::with(['teacherDetail.user'])
            ->where('organization_id', $organization->id)
            ->whereDoesntHave('idCards', $active)
            ->get();
    }

    /**
     * Create (only) the card row for one person.
     */
    public function createCardFor(Organization $organization, string $type, $person, string $expiryDate, ?int $userId = null)
    {
        $data = [
            'card_number'     => $this->generateCardNumber($organization, $type, $person),
            'organization_id' => $organization->id,
            'user_id'         => $userId ?? 0,
            'issue_date'      => now(),
            'expiry_date'     => $expiryDate,
            'status'          => 'active',
        ];

        switch ($type) {
            case 'student':
                $data['student_detail_id'] = $person->id;
                return StudentIdCard::create($data);
            case 'teacher':
                $data['teacher_detail_id'] = $person->id;
                return TeacherIdCard::create($data);
            default:
                $data['admin_employee_id'] = $person->id;
                return EmployeeIdCard::create($data);
        }
    }

    public function modelClassFor(string $type): string
    {
        return match ($type) {
            'student' => StudentIdCard::class,
            'teacher' => TeacherIdCard::class,
            default   => EmployeeIdCard::class,
        };
    }

    public function generateCardNumber(Organization $organization, string $type, $person, string $prefix = 'ID'): string
    {
        $year = now()->format('y');
        $typePrefix = match ($type) {
            'student' => 'STU',
            'teacher' => 'TCH',
            default   => 'EMP',
        };

        $model = $this->modelClassFor($type);
        $base = "{$prefix}{$typePrefix}{$organization->id}{$year}{$person->id}" . mt_rand(1000, 9999);

        $cardNumber = $base;
        $counter = 1;
        while ($model::where('card_number', $cardNumber)->exists()) {
            $cardNumber = "{$base}-{$counter}";
            $counter++;
        }

        return $cardNumber;
    }

    public function personName($person, string $type): string
    {
        return match ($type) {
            'student' => $person->full_name ?? ($person->user->name ?? 'Unknown'),
            'teacher' => $person->user->name ?? 'Unknown',
            default   => $person->name ?? 'Unknown',
        };
    }

    /**
     * Resolve a stored image/photo value to a browser-usable URL. Accepts full
     * URLs (S3) as-is, otherwise treats it as a public-disk storage path.
     */
    public function resolvePhoto(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (Str::startsWith($value, ['http://', 'https://', 'data:'])) {
            return $value;
        }
        return Storage::url($value);
    }

    /**
     * Normalise a card (+ its person) into a flat array the card template can
     * render. Shared by the print page and the on-screen preview so the design
     * stays identical in both places.
     */
    public function cardViewData($card, string $type): array
    {
        $organization = $card->organization;
        $info = $organization?->schoolInfo;

        $school = [
            'name'    => $organization->name ?? 'School',
            'logo'    => $this->resolvePhoto($organization->logo ?? null),
            'address' => $info->school_address ?? ($organization->address ?? null),
            'website' => $info->website_url ?? null,
            'email'   => $info->school_email ?? ($organization->email ?? null),
            'phone'   => $info->school_mobile ?? ($organization->mobile_number ?? null),
        ];

        $data = [
            'type'        => $type,
            'school'      => $school,
            'card_number' => $card->card_number,
            'issue_date'  => optional($card->issue_date)->format('d M Y') ?? '—',
            'expiry_date' => optional($card->expiry_date)->format('d M Y') ?? '—',
            'status'      => $card->status,
            'qr_code'     => $card->qr_code,
            'photo'       => null,
            'name'        => '—',
            'subtitle'    => ucfirst($type),
            'front_rows'  => [],
            'back_mode'   => $type === 'student' ? 'transport' : 'terms',
            'transport'   => null,
        ];

        if ($type === 'student') {
            $p = $card->studentDetail;
            $data['photo']    = $this->resolvePhoto($p->image ?? ($p->user->image ?? null));
            $data['name']     = $p->full_name ?? ($p->user->name ?? '—');
            $cls = ($p->standard->name ?? '—') . ($p->section ? ' - ' . $p->section->name : '');
            $data['subtitle'] = trim($cls);
            $data['front_rows'] = [
                'Reg No'        => $p->admission_no ?? '—',
                'Class'         => $p->standard->name ?? '—',
                'Section'       => $p->section->name ?? '—',
                'Father Name'   => $p->father_name ?? '—',
                'Mobile'        => $p->phone ?? '—',
                'Address'       => $p->local_address ?? ($p->permanent_address ?? '—'),
            ];

            $transport = method_exists($p, 'activeTransportation') ? $p->activeTransportation() : null;
            if ($transport) {
                $driver = $transport->driver;
                $data['transport'] = [
                    'Route'    => $transport->route_name ?? '—',
                    'Pickup'   => $transport->pickup_location ?? '—',
                    'Drop'     => $transport->drop_location ?? '—',
                    'Time'     => $transport->pickup_time ?? '—',
                    'Vehicle'  => $driver?->vehicle_no ?? '—',
                    'Driver'   => $driver?->user?->name ?? '—',
                    'Contact'  => $driver?->phone ?? '—',
                ];
            }
        } elseif ($type === 'teacher') {
            $p = $card->teacherDetail;
            $data['photo']    = $this->resolvePhoto($p->user->image ?? null);
            $data['name']     = $p->user->name ?? '—';
            $data['subtitle'] = 'Teacher';
            $data['front_rows'] = [
                'Employee ID'  => $p->employee_id ?? '—',
                'Designation'  => 'Teacher',
                'Qualification' => $p->qualification ?? '—',
                'Mobile'       => $p->phone ?? '—',
                'Joining Date' => $p->date_of_joining ? \Carbon\Carbon::parse($p->date_of_joining)->format('d M Y') : '—',
                'Address'      => $p->address ?? '—',
            ];
        } else {
            $p = $card->adminEmployee;
            $data['photo']    = $this->resolvePhoto($p->photo ?? null);
            $data['name']     = $p->name ?? '—';
            $data['subtitle'] = $p->designation ?? ucfirst($p->type ?? 'Employee');
            $data['front_rows'] = [
                'Emp ID'       => 'EMP-' . ($p->id ?? '—'),
                'Designation'  => $p->designation ?? '—',
                'Mobile'       => $p->mobile ?? '—',
                'Email'        => $p->email ?? '—',
                'Joining Date' => $p->joining_date ? \Carbon\Carbon::parse($p->joining_date)->format('d M Y') : '—',
                'Address'      => $p->address ?? '—',
            ];
        }

        return $data;
    }

    /**
     * Build a verification QR payload. Returns base64 PNG or null on failure.
     */
    public function generateQrCode($card, $person, Organization $organization, string $type): ?string
    {
        try {
            $qrData = [
                'card' => [
                    'number'      => $card->card_number,
                    'issue_date'  => optional($card->issue_date)->format('Y-m-d'),
                    'expiry_date' => optional($card->expiry_date)->format('Y-m-d'),
                    'status'      => $card->status,
                    'type'        => $type,
                ],
                'organization' => [
                    'id'      => $organization->id,
                    'name'    => $organization->name,
                    'address' => $organization->address,
                ],
                'verification' => [
                    'timestamp' => now()->timestamp,
                    'url'       => Route::has($type . '.verify')
                        ? route($type . '.verify', $card->card_number)
                        : url('/id-card/verify/' . $card->card_number),
                ],
            ];

            if ($type === 'student') {
                $qrData['student'] = [
                    'id'           => $person->id,
                    'full_name'    => $person->full_name,
                    'admission_no' => $person->admission_no,
                    'father_name'  => $person->father_name,
                    'phone'        => $person->phone,
                    'class'        => $person->standard->name ?? null,
                    'section'      => $person->section->name ?? null,
                ];
            } elseif ($type === 'teacher') {
                $qrData['teacher'] = [
                    'id'          => $person->id,
                    'full_name'   => $person->user->name ?? 'N/A',
                    'employee_id' => $person->employee_id,
                    'phone'       => $person->phone,
                ];
            } else {
                $qrData['employee'] = [
                    'id'          => $person->id,
                    'full_name'   => $person->name,
                    'designation' => $person->designation,
                    'mobile'      => $person->mobile,
                ];
            }

            $json = json_encode($qrData, JSON_PRETTY_PRINT);

            // Drawn here — no call to another website for every card.
            $png = $this->localQrPng($json);
            if ($png !== null) {
                return base64_encode($png);
            }

            if (class_exists(\SimpleSoftwareIO\QrCode\QrCode::class)) {
                $png = QrCode::format('png')->size(250)->margin(2)
                    ->errorCorrection('H')->encoding('UTF-8')->generate($json);

                return base64_encode($png);
            }

            // A slow answer must not hold the request up: give it 5 seconds.
            $context = stream_context_create([
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => ['timeout' => 5],
            ]);
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=2&ecc=H&data=' . urlencode($json);
            $image = @file_get_contents($url, false, $context);

            return $image !== false ? base64_encode($image) : null;
        } catch (\Throwable $e) {
            Log::error('QR Code Generation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * A QR code as PNG bytes — black on white, error correction H, two modules
     * of quiet zone, at least 250 px across (as the old image service drew it) —
     * encoded by bacon/bacon-qr-code and painted with GD. Null without GD or if
     * encoding fails, so the caller falls back.
     */
    private function localQrPng(string $text): ?string
    {
        if (!function_exists('imagecreate') || !class_exists(Encoder::class)) {
            return null;
        }

        try {
            $matrix = Encoder::encode($text, ErrorCorrectionLevel::H(), 'UTF-8')->getMatrix();
            $modules = $matrix->getWidth();
            $margin = 2;
            // Whole pixels per module keep every module the same size.
            $scale = max(2, (int) ceil(250 / ($modules + 2 * $margin)));
            $size = ($modules + 2 * $margin) * $scale;

            $img = imagecreate($size, $size);
            imagecolorallocate($img, 255, 255, 255); // the first colour is the background
            $black = imagecolorallocate($img, 0, 0, 0);

            for ($y = 0; $y < $modules; $y++) {
                for ($x = 0; $x < $modules; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        $px = ($x + $margin) * $scale;
                        $py = ($y + $margin) * $scale;
                        imagefilledrectangle($img, $px, $py, $px + $scale - 1, $py + $scale - 1, $black);
                    }
                }
            }

            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            return $png !== false && $png !== '' ? $png : null;
        } catch (\Throwable $e) {
            Log::warning('Local QR drawing failed, falling back: ' . $e->getMessage());
            return null;
        }
    }
}
