<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\Transportation;
use App\Models\SchoolWebsiteEnquiry;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/website/school-admission — a school website's Online Admission
 * form (user's ask of 2026-11-25): every field of the admin's Add Student
 * form, a student photo and a signature of 15 KB to 25 KB each. One sending
 * lands in both of the admin's inboxes: Admissions (a pending admission, the
 * photo and signature as its documents, the form's answers as its details)
 * and Enquiries → Website Enquiries (an Online Admission enquiry).
 */
class SchoolAdmissionController extends Controller
{
    /** The photo's and the signature's size, in KB (1 KB = 1024 bytes). */
    public const MIN_KB = 15;
    public const MAX_KB = 25;

    public const THANKS = 'Thank you! Your admission form has been sent to the school. The school office will get in touch with you soon.';

    public function store(Request $request): JsonResponse
    {
        $image = ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'min:' . self::MIN_KB, 'max:' . self::MAX_KB];
        $size  = self::MIN_KB . ' KB to ' . self::MAX_KB . ' KB';

        $v = $request->validate([
            'organization_id'     => 'required|integer|exists:organizations,id',
            'name'                => 'required|string|max:50',
            'email'               => 'nullable|email:rfc|max:191',
            'mobile'              => 'required|digits:10',
            'gender'              => 'required|in:' . implode(',', Constants::GENDER),
            'standard_id'         => 'nullable|integer',
            'class_name'          => 'required_without:standard_id|nullable|string|max:60',
            'section_id'          => 'nullable|integer',
            'dob'                 => 'required|date|before:today',
            'date_of_admission'   => 'nullable|date',
            'religion'            => 'nullable|string|max:20',
            'aadhar_no'           => 'nullable|digits:12',
            'father_name'         => 'required|string|max:50',
            'mother_name'         => 'nullable|string|max:50',
            'appar_id'            => 'nullable|string|max:25',
            'registration_number' => 'nullable|string|max:25',
            'state'               => 'nullable|string|max:60',
            'city'                => 'nullable|string|max:80',
            'pincode'             => 'nullable|digits:6',
            'transport'           => 'required|in:0,1',
            'route_id'            => 'nullable|integer',
            'local_address'       => 'nullable|string|max:250',
            'permanent_address'   => 'nullable|string|max:250',
            'photo'               => $image,
            'signature'           => $image,
        ], [
            'name.required'          => 'Please enter the full name.',
            'name.max'               => 'Full name may not be longer than 50 characters.',
            'email.email'            => 'Please enter a valid email address.',
            'mobile.required'        => 'Please enter the mobile number.',
            'mobile.digits'          => 'Mobile number must be exactly 10 digits.',
            'gender.required'        => 'Please select the gender.',
            'class_name.required_without' => 'Please select a class.',
            'dob.required'           => 'Please enter the date of birth.',
            'dob.before'             => 'The date of birth must be before today.',
            'aadhar_no.digits'       => 'Aadhar number must be exactly 12 digits.',
            'father_name.required'   => "Please enter the father's name.",
            'father_name.max'        => "Father's name may not be longer than 50 characters.",
            'mother_name.max'        => "Mother's name may not be longer than 50 characters.",
            'pincode.digits'         => 'Pincode must be exactly 6 digits.',
            'photo.required'         => 'Please add the student photo.',
            'photo.image'            => 'The student photo must be a JPG, PNG or WebP image.',
            'photo.mimes'            => 'The student photo must be a JPG, PNG or WebP image.',
            'photo.min'              => "The student photo must be {$size}.",
            'photo.max'              => "The student photo must be {$size}.",
            'signature.required'     => 'Please add the signature.',
            'signature.image'        => 'The signature must be a JPG, PNG or WebP image.',
            'signature.mimes'        => 'The signature must be a JPG, PNG or WebP image.',
            'signature.min'          => "The signature must be {$size}.",
            'signature.max'          => "The signature must be {$size}.",
        ]);

        // The bot trap (a field no one sees), as on the website's other forms.
        if (filled($request->input('company'))) {
            return response()->json(['success' => true, 'message' => self::THANKS]);
        }

        $orgId = (int) $v['organization_id'];

        // The class, its section and the route must be the school's own.
        $standard = null;
        if (! empty($v['standard_id'])) {
            $standard = Standard::where('organization_id', $orgId)->where('is_active', true)->find((int) $v['standard_id']);
            if (! $standard) {
                throw ValidationException::withMessages(['standard_id' => 'Please select a class.']);
            }
        }
        $section = null;
        if ($standard) {
            $sections = Section::where('standard_id', $standard->id)->where('is_active', true)->get(['id', 'name']);
            if ($sections->isNotEmpty()) {
                $section = $sections->firstWhere('id', (int) ($v['section_id'] ?? 0));
                if (! $section) {
                    throw ValidationException::withMessages(['section_id' => 'Please select a section.']);
                }
            }
        }
        $route = null;
        if ((string) $v['transport'] === '1') {
            $route = Transportation::where('organization_id', $orgId)->where('is_active', true)->find((int) ($v['route_id'] ?? 0));
            if (! $route) {
                throw ValidationException::withMessages(['route_id' => 'Please select a transport route.']);
            }
        }

        $className = $standard ? trim((string) $standard->name) : trim((string) $v['class_name']);
        $date      = fn ($d) => $d ? Carbon::parse($d)->format('d M Y') : '';

        // Every answer, labelled and in the order of the admin's Add Student form.
        $answers = [
            'Full Name'           => $v['name'],
            'Email'               => $v['email'] ?? '',
            'Mobile'              => $v['mobile'],
            'Gender'              => ucfirst($v['gender']),
            'Class'               => $className,
            'Section'             => $section ? $section->name : '',
            'Date of Birth'       => $date($v['dob']),
            'Date of Admission'   => $date($v['date_of_admission'] ?? null),
            'Religion'            => $v['religion'] ?? '',
            'Aadhar Number'       => $v['aadhar_no'] ?? '',
            "Father's Name"       => $v['father_name'],
            "Mother's Name"       => $v['mother_name'] ?? '',
            'Apaar ID'            => $v['appar_id'] ?? '',
            'Registration Number' => $v['registration_number'] ?? '',
            'State'               => $v['state'] ?? '',
            'City'                => $v['city'] ?? '',
            'Pincode'             => $v['pincode'] ?? '',
            'Transport Required'  => $route ? 'Yes' : 'No',
            'Route'               => $route ? $route->route_name . ' — ₹' . number_format((float) $route->monthly_fee, 0) . '/mo' : '',
            'Local Address'       => $v['local_address'] ?? '',
            'Permanent Address'   => $v['permanent_address'] ?? '',
        ];
        $rows = [];
        foreach ($answers as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        $dir   = 'admin/admissions/website/' . $orgId;
        $files = [
            ['label' => 'Student Photo', 'file' => $request->file('photo')->store($dir, 's3'), 'original_name' => $request->file('photo')->getClientOriginalName()],
            ['label' => 'Signature',     'file' => $request->file('signature')->store($dir, 's3'), 'original_name' => $request->file('signature')->getClientOriginalName()],
        ];

        $message = implode("\n", array_map(
            fn ($r) => $r['label'] . ': ' . $r['value'],
            array_filter($rows, fn ($r) => ! in_array($r['label'], ['Full Name', 'Email', 'Mobile'], true)),
        ));

        DB::transaction(function () use ($orgId, $v, $standard, $className, $rows, $files, $message) {
            SchoolWebsiteEnquiry::create([
                'organization_id' => $orgId,
                'type'            => 'admission',
                'name'            => $v['name'],
                'email'           => $v['email'] ?? null,
                'phone'           => $v['mobile'],
                'subject'         => 'Online Admission' . ($className !== '' ? ' — ' . $className : ''),
                'message'         => $message,
                'data'            => array_merge($rows, array_map(fn ($f) => ['label' => $f['label'], 'value' => '', 'file' => $f['file']], $files)),
            ]);

            AdmissionEnquiry::create([
                'organization_id' => $orgId,
                'student_name'    => $v['name'],
                'email'           => $v['email'] ?? null,
                'mobile'          => $v['mobile'],
                'guardian_name'   => $v['father_name'],
                'address'         => ($v['local_address'] ?? null) ?: ($v['permanent_address'] ?? null),
                'standard_id'     => $standard?->id,
                'admission_fee'   => 0,
                'status'          => 'pending',
                'source'          => AdmissionEnquiry::SOURCE_WEBSITE,
                'details'         => $rows,
                'documents'       => array_map(fn ($f) => ['name' => $f['label'], 'path' => $f['file'], 'original_name' => $f['original_name']], $files),
            ]);
        });

        return response()->json(['success' => true, 'message' => self::THANKS]);
    }
}
