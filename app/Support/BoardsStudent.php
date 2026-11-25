<?php

namespace App\Support;

use App\Livewire\Concerns\HandlesStudentFeeView;
use App\Models\Admin\Fee\PaymentQrCode;
use App\Models\Admin\Transportation;
use App\Models\Boards\BoardSetting;
use App\Models\Boards\BoardStudent;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Crypt;

/**
 * The website's Boards login (user's ask of 2026-11-25): a Class 9–12
 * student of the school signs in with their name, the first word of their
 * father's name and their date of birth — all three as the school has them,
 * or nothing — and sees their personal and class details, their fee and
 * the school's payment QR. The admin's More → Boards shows the same details
 * for each of those students.
 *
 * Names are compared without case, dots or extra spaces; the father's name
 * by its first word (a "Mr." or "Shri" in front of it is passed over).
 */
class BoardsStudent
{
    use HandlesStudentFeeView;

    /** How long a sign-in lasts in the visitor's tab. */
    public const MINUTES = 120;

    /** Words that may stand before a father's name and are not it. */
    private const TITLES = ['mr', 'shri', 'sri', 'sh', 'late', 'dr', 'er', 'adv'];

    public function __construct(private int $orgId)
    {
    }

    protected function orgId(): int
    {
        return $this->orgId;
    }

    /** A name as it is compared: lower case, letters and digits only, single spaces. */
    public static function normalise(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text));
        $text = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** The words of a father's name a sign-in may give as its first: the very first, and the first past any title. */
    public static function firstWords(?string $name): array
    {
        $words = explode(' ', self::normalise($name));
        $words = array_values(array_filter($words, fn ($w) => $w !== ''));
        if (! $words) {
            return [];
        }

        $out = [$words[0]];
        foreach ($words as $w) {
            if (! in_array($w, self::TITLES, true)) {
                $out[] = $w;
                break;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * The Class 9–12 student whose name, father's first name and date of
     * birth are all the ones given (dob as Y-m-d), or null. A student whose
     * login the school has switched off is not signed in.
     */
    public function find(string $name, string $father, string $dob): ?StudentDetail
    {
        $name   = self::normalise($name);
        $typed  = self::firstWords($father);
        $ids    = BoardClasses::standardIds($this->orgId);
        if ($name === '' || ! $typed || ! $ids) {
            return null;
        }

        return StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId)
            ->whereIn('standard_id', $ids)
            ->whereDate('dob', $dob)
            ->orderBy('id')
            ->get()
            ->first(function (StudentDetail $d) use ($name, $typed) {
                if (! $d->user || ! $d->user->is_active) {
                    return false;
                }
                $names = array_filter([self::normalise($d->full_name), self::normalise($d->user->name)]);
                if (! in_array($name, $names, true)) {
                    return false;
                }

                return (bool) array_intersect($typed, self::firstWords($d->father_name));
            });
    }

    /** A token the visitor's tab keeps to stay signed in, for this one student. */
    public function token(StudentDetail $student): string
    {
        return Crypt::encryptString(json_encode([
            'o' => $this->orgId,
            's' => (int) $student->id,
            'e' => now()->addMinutes(self::MINUTES)->timestamp,
        ]));
    }

    /** The student a token is for, while it lasts and they are still a Class 9–12 student of this school. */
    public function fromToken(?string $token): ?StudentDetail
    {
        try {
            $data = json_decode(Crypt::decryptString((string) $token), true);
        } catch (\Throwable $e) {
            return null;
        }
        if (! is_array($data) || (int) ($data['o'] ?? 0) !== $this->orgId || (int) ($data['e'] ?? 0) < now()->timestamp) {
            return null;
        }

        $student = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId)
            ->whereIn('standard_id', BoardClasses::standardIds($this->orgId))
            ->find((int) ($data['s'] ?? 0));

        return $student && $student->user && $student->user->is_active ? $student : null;
    }

    /** The student's board roll number and admit card row, if the school has added either. */
    public function boardRecord(int $studentId): ?BoardStudent
    {
        try {
            return BoardStudent::where('organization_id', $this->orgId)->where('student_detail_id', $studentId)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;   // before the migration
        }
    }

    /** "XXXX XXXX 1234" — the Aadhaar number with only its last four digits. */
    public static function maskAadhaar(?string $no): string
    {
        $digits = preg_replace('/\D/', '', (string) $no);

        return $digits === '' ? '' : 'XXXX XXXX ' . substr($digits, -4);
    }

    /**
     * Everything the Boards page shows a student, and the admin's View: their
     * personal details, class details, their fee (as the admin's View Fee
     * works it out), the school's payment QR, and their board roll number and
     * admit card. On the website ($forSchool false) the Aadhaar number shows
     * only its last four digits, and the roll number and the admit card only
     * once the student has paid the share of the fee the school asks for
     * them (More → Boards → Settings); the school sees everything.
     */
    public function data(StudentDetail $student, bool $forSchool = false): array
    {
        $student->loadMissing(['user', 'standard', 'section']);
        $org  = Organization::find($this->orgId);
        $date = fn ($d) => $d ? $d->format('d M Y') : '';

        $route = Transportation::where('organization_id', $this->orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $student->id))
            ->orderByDesc('is_active')
            ->first();

        $personal = [
            'Full Name'          => $student->full_name ?: ($student->user->name ?? ''),
            "Father's Name"      => $student->father_name,
            "Mother's Name"      => $student->mother_name,
            'Date of Birth'      => $date($student->dob),
            'Gender'             => $student->gender ? ucfirst($student->gender) : '',
            'Religion'           => $student->religion,
            'Mobile'             => $student->phone,
            'Email'              => $student->email ?: ($student->user->email ?? ''),
            'Aadhar Number'      => $forSchool ? $student->aadhar_no : self::maskAadhaar($student->aadhar_no),
            'Apaar ID'           => $student->appar_id,
            'Registration Number' => $student->registration_number,
            'Local Address'      => $student->local_address,
            'Permanent Address'  => $student->permanent_address,
            'City'               => $student->city,
            'State'              => $student->state,
            'Pincode'            => $student->pincode,
        ];

        $class = [
            'Class'             => $student->standard->name ?? '',
            'Section'           => $student->section->name ?? '',
            'Roll No'           => $student->roll_no,
            'Admission No'      => $student->admission_no,
            'Date of Admission' => $date($student->date_of_admission),
            'Board'             => BoardName::short($student->standard->board ?? null) ?: BoardName::short($org?->education_board),
            'Transport'         => $route ? $route->route_name : 'No',
        ];

        $keep = fn (array $rows) => array_filter(array_map(fn ($v) => trim((string) $v), $rows), fn ($v) => $v !== '');

        $qr      = PaymentQrCode::activeForOrg($this->orgId);
        $fee     = $this->buildStudentFeeView((int) $student->id);
        $balance = (float) ($fee['totals']['remaining'] ?? 0);

        // The board roll number and admit card, behind the school's fee rules.
        $net   = (float) ($fee['totals']['net'] ?? 0);
        $paid  = (float) ($fee['totals']['paid'] ?? 0);
        $pct   = $net > 0 ? min(100.0, $paid / $net * 100) : 100.0;
        $rules = BoardSetting::rules($this->orgId);
        $rec   = $this->boardRecord((int) $student->id);
        $board = [
            'paid_pct'   => floor($pct * 10) / 10,
            'roll_need'  => $rules['roll'],
            'admit_need' => $rules['admit'],
            'roll_ok'    => $pct + 1e-9 >= $rules['roll'],
            'admit_ok'   => $pct + 1e-9 >= $rules['admit'],
            'has_roll'   => trim((string) $rec?->roll_number) !== '',
            'has_admit'  => (bool) $rec?->admit_card_path,
        ];
        $board['roll_number'] = ($forSchool || $board['roll_ok']) && $board['has_roll'] ? trim($rec->roll_number) : null;
        $board['admit_url']   = ($forSchool || $board['admit_ok']) && $board['has_admit'] ? $rec->admitCardUrl() : null;
        $board['admit_name']  = $board['admit_url'] ? ($rec->admit_card_name ?: 'Admit Card') : null;

        return [
            'board'    => $board,
            'name'     => $personal['Full Name'],
            'photo'    => ($student->user?->image) ?: ($student->image ?: null),
            'school'   => $org?->name ?? '',
            'personal' => $keep($personal),
            'class'    => $keep($class),
            'fee'      => $fee,
            'qr'       => $qr ? [
                'image'        => $qr->imageUrl(),
                'upi_id'       => $qr->upi_id,
                'payee'        => $qr->payee_name ?: ($org?->name ?? ''),
                'instructions' => $qr->instructions,
                // Pay the fee still due straight from a UPI app on the phone.
                'link'         => $qr->upi_id && $balance > 0
                    ? UpiQr::link($qr->upi_id, $qr->payee_name ?: ($org?->name ?? 'School'), $balance, 'Fee ' . ($student->admission_no ?: $personal['Full Name']))
                    : null,
            ] : null,
        ];
    }
}
