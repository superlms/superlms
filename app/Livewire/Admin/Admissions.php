<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdmissionExamPaper;
use App\Models\Organization;
use App\Models\Student\Standard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Admissions extends Component
{
    use WireUiActions, WithPagination, WithFileUploads;

    // ─── Active tab ──────────────────────────────────────────────────────────
    public string $activeTab = 'admissions'; // 'admissions' | 'papers'

    // ─── Add/Edit Student form ───────────────────────────────────────────────
    public string $studentName  = '';
    public string $email        = '';
    public string $mobile       = '';
    public string $guardianName = '';
    public string $address      = '';
    public string $standardId   = '';
    public string $stream       = '';
    public string $admissionFee = '';
    public $editEnquiryId       = null;
    public bool $enquiryModalOpen = false;

    // ─── Update (result) form ────────────────────────────────────────────────
    public $updateEnquiryId      = null;
    public bool $updateModalOpen = false;
    public string $totalMarks    = '';
    public string $obtainedMarks = '';
    public string $remarks       = '';
    public $resultPdf            = null;

    // ─── Fee collection slide-in ─────────────────────────────────────────────
    public $feeEnquiryId          = null;
    public bool $feeModalOpen     = false;
    public string $collectedAmount = '';
    public string $paymentMode     = 'cash';
    public string $collectedBy     = '';
    public string $feeCollectedAt  = '';

    // ─── View slide-in ───────────────────────────────────────────────────────
    public bool $viewModalOpen   = false;
    public array $viewEnquiryData = [];

    // ─── Filters (admissions tab) ────────────────────────────────────────────
    public string $filterStandard = '';
    public string $filterMonth    = '';
    public string $search         = '';
    public string $searchInput    = '';   // debounce-free input, applied on Search btn

    // ─── Exam Papers ─────────────────────────────────────────────────────────
    public bool $paperModalOpen     = false;
    public bool $editPaperModalOpen = false;
    public $editPaperId             = null;
    public string $paperStandardId  = '';
    public string $paperTitle       = '';
    public $paperFile               = null;
    public string $editPaperTitle   = '';
    public string $editPaperStandardId = '';
    public $editPaperFile           = null;

    public string $filterPaperStandard = '';

    // ─── Delete confirms ─────────────────────────────────────────────────────
    public $pendingDeleteEnquiryId = null;
    public $pendingDeletePaperId   = null;

    public int $perPage = 10;

    protected $queryString = [
        'activeTab'           => ['except' => 'admissions'],
        'filterStandard'      => ['except' => ''],
        'filterMonth'         => ['except' => ''],
        'search'              => ['except' => ''],
        'filterPaperStandard' => ['except' => ''],
    ];

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    // ─── Tabs ────────────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ─── Search button ───────────────────────────────────────────────────────

    public function applySearch(): void
    {
        $this->search = $this->searchInput;
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->reset(['filterStandard', 'filterMonth', 'search', 'searchInput']);
        $this->resetPage();
    }

    // ═════════════════════════════════════════════════════════════════
    //  ADMISSIONS TAB — Add/Edit/View/Delete Student Enquiry
    // ═════════════════════════════════════════════════════════════════

    public function openEnquiryModal(?int $id = null): void
    {
        $this->resetEnquiryForm();
        $this->editEnquiryId    = $id;
        $this->enquiryModalOpen = true;

        if ($id) {
            $e = AdmissionEnquiry::where('id', $id)
                ->where('organization_id', $this->orgId())
                ->first();
            if (!$e) return;

            $this->studentName  = (string) ($e->student_name ?? '');
            $this->email        = (string) ($e->email ?? '');
            $this->mobile       = (string) ($e->mobile ?? '');
            $this->guardianName = (string) ($e->guardian_name ?? '');
            $this->address      = (string) ($e->address ?? '');
            $this->standardId   = (string) ($e->standard_id ?? '');
            $this->stream       = (string) ($e->stream ?? '');
            $this->admissionFee = (string) ($e->admission_fee ?? '');
        }
    }

    public function closeEnquiryModal(): void
    {
        $this->enquiryModalOpen = false;
        $this->resetEnquiryForm();
    }

    public function saveEnquiry(): void
    {
        $this->validate([
            'studentName'  => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'mobile'       => 'required|string|max:20',
            'guardianName' => 'required|string|max:255',
            'address'      => 'nullable|string|max:1000',
            'standardId'   => 'nullable',
            'stream'       => 'nullable|string|max:255',
            'admissionFee' => 'required|numeric|min:0',
        ]);

        try {
            $data = [
                'student_name'  => $this->studentName,
                'email'         => $this->email ?: null,
                'mobile'        => $this->mobile,
                'guardian_name' => $this->guardianName,
                'address'       => $this->address ?: null,
                'standard_id'   => $this->standardId ?: null,
                'stream'        => $this->stream ?: null,
                'admission_fee' => $this->admissionFee,
            ];

            if ($this->editEnquiryId) {
                $record = AdmissionEnquiry::where('id', $this->editEnquiryId)
                    ->where('organization_id', $this->orgId())
                    ->first();

                if ($record) {
                    $record->update($data);
                    $this->notification()->success('Enquiry updated successfully!');
                }
            } else {
                $data['organization_id'] = $this->orgId();
                $data['status']          = 'pending';
                AdmissionEnquiry::create($data);
                $this->notification()->success('Student added successfully!');
            }

            $this->closeEnquiryModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    public function editEnquiry(int $id): void
    {
        $this->openEnquiryModal($id);
    }

    public function viewEnquiry(int $id): void
    {
        $enquiry = AdmissionEnquiry::with('standard')
            ->where('id', $id)
            ->where('organization_id', $this->orgId())
            ->first();
        if (!$enquiry) return;

        $this->viewEnquiryData = [
            'id'             => $enquiry->id,
            'student_name'   => $enquiry->student_name,
            'email'          => $enquiry->email ?? '—',
            'mobile'         => $enquiry->mobile,
            'guardian_name'  => $enquiry->guardian_name,
            'address'        => $enquiry->address ?? '—',
            'class'          => $enquiry->standard->name ?? '—',
            'stream'         => $enquiry->stream ?? '—',
            'admission_fee'  => $enquiry->admission_fee,
            'total_marks'    => $enquiry->total_marks,
            'obtained_marks' => $enquiry->obtained_marks,
            'remarks'        => $enquiry->remarks ?? '—',
            'result_pdf'     => $enquiry->result_pdf,
            'status'         => $enquiry->status,
            'created_at'     => $enquiry->created_at->format('d M Y, g:i A'),
        ];
        $this->viewModalOpen = true;
    }

    public function closeViewModal(): void
    {
        $this->viewModalOpen   = false;
        $this->viewEnquiryData = [];
    }

    public function deleteEnquiry(int $id): void { $this->pendingDeleteEnquiryId = $id; }
    public function cancelDeleteEnquiry(): void  { $this->pendingDeleteEnquiryId = null; }

    public function doDeleteEnquiry(): void
    {
        $enquiry = AdmissionEnquiry::where('id', $this->pendingDeleteEnquiryId)
            ->where('organization_id', $this->orgId())
            ->first();

        if ($enquiry) {
            if ($enquiry->result_pdf) {
                Storage::disk('s3')->delete($enquiry->result_pdf);
            }
            $enquiry->delete();
            $this->notification()->success('Enquiry deleted!');
        }
        $this->pendingDeleteEnquiryId = null;
    }

    // ─── Update Result ───────────────────────────────────────────────────────

    public function openUpdateModal(int $id): void
    {
        $this->resetUpdateForm();
        $this->updateEnquiryId = $id;
        $this->updateModalOpen = true;

        $enquiry = AdmissionEnquiry::where('id', $id)
            ->where('organization_id', $this->orgId())
            ->first();
        if ($enquiry) {
            $this->totalMarks    = (string) ($enquiry->total_marks ?? '');
            $this->obtainedMarks = (string) ($enquiry->obtained_marks ?? '');
            $this->remarks       = (string) ($enquiry->remarks ?? '');
        }
    }

    public function closeUpdateModal(): void
    {
        $this->updateModalOpen = false;
        $this->resetUpdateForm();
    }

    public function saveUpdate(): void
    {
        $this->validate([
            'totalMarks'    => 'required|numeric|min:0',
            'obtainedMarks' => 'required|numeric|min:0',
            'remarks'       => 'nullable|string|max:2000',
            'resultPdf'     => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $enquiry = AdmissionEnquiry::where('id', $this->updateEnquiryId)
                ->where('organization_id', $this->orgId())
                ->first();
            if (!$enquiry) return;

            $updateData = [
                'total_marks'    => $this->totalMarks,
                'obtained_marks' => $this->obtainedMarks,
                'remarks'        => $this->remarks ?: null,
                'status'         => 'updated',
            ];

            if ($this->resultPdf) {
                if ($enquiry->result_pdf) {
                    Storage::disk('s3')->delete($enquiry->result_pdf);
                }
                $path = $this->resultPdf->store(
                    'admin/admissions/result-pdfs/' . $this->orgId(),
                    's3'
                );
                $updateData['result_pdf'] = $path;
            }

            $enquiry->update($updateData);
            $this->notification()->success('Result updated successfully!');
            $this->closeUpdateModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ─── Fee collection slide-in ─────────────────────────────────────────────

    public function openFeeModal(int $id): void
    {
        $this->resetValidation();
        $enquiry = AdmissionEnquiry::where('id', $id)
            ->where('organization_id', $this->orgId())->first();
        if (!$enquiry) return;

        $this->feeEnquiryId    = $id;
        $this->collectedAmount = (string) ($enquiry->collected_amount ?? $enquiry->admission_fee ?? '');
        $this->paymentMode     = $enquiry->payment_mode ?: 'cash';
        $this->collectedBy     = $enquiry->collected_by ?: (Auth::user()->name ?? '');
        $this->feeCollectedAt  = $enquiry->fee_collected_at
            ? $enquiry->fee_collected_at->format('Y-m-d')
            : now()->toDateString();
        $this->feeModalOpen    = true;
    }

    public function closeFeeModal(): void
    {
        $this->feeModalOpen = false;
        $this->reset(['feeEnquiryId', 'collectedAmount', 'paymentMode', 'collectedBy', 'feeCollectedAt']);
        $this->resetValidation();
    }

    public function saveFee(): void
    {
        $this->validate([
            'collectedAmount' => 'required|numeric|min:0',
            'paymentMode'     => 'required|in:cash,online,upi,cheque,card',
            'collectedBy'     => 'required|string|max:255',
            'feeCollectedAt'  => 'required|date',
        ]);

        try {
            $enquiry = AdmissionEnquiry::where('id', $this->feeEnquiryId)
                ->where('organization_id', $this->orgId())->first();
            if (!$enquiry) return;

            $enquiry->update([
                'collected_amount' => $this->collectedAmount,
                'payment_mode'     => $this->paymentMode,
                'collected_by'     => $this->collectedBy,
                'fee_collected_at' => $this->feeCollectedAt,
            ]);

            $this->notification()->success('Fee collection updated successfully!');
            $this->closeFeeModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    // ─── Reset helpers ───────────────────────────────────────────────────────

    private function resetEnquiryForm(): void
    {
        $this->reset([
            'editEnquiryId',
            'studentName', 'email', 'mobile', 'guardianName',
            'address', 'standardId', 'stream', 'admissionFee',
        ]);
        $this->resetValidation();
    }

    private function resetUpdateForm(): void
    {
        $this->reset([
            'updateEnquiryId',
            'totalMarks', 'obtainedMarks', 'remarks', 'resultPdf',
        ]);
        $this->resetValidation();
    }

    // ═════════════════════════════════════════════════════════════════
    //  EXAM PAPERS TAB
    // ═════════════════════════════════════════════════════════════════

    public function openPaperModal(): void
    {
        $this->reset(['paperStandardId', 'paperTitle', 'paperFile']);
        $this->paperModalOpen = true;
    }

    public function closePaperModal(): void
    {
        $this->paperModalOpen = false;
        $this->reset(['paperStandardId', 'paperTitle', 'paperFile']);
    }

    public function openEditPaperModal(int $id): void
    {
        $paper = AdmissionExamPaper::where('id', $id)
            ->where('organization_id', $this->orgId())
            ->firstOrFail();

        $this->editPaperId         = $id;
        $this->editPaperTitle      = (string) ($paper->title ?? '');
        $this->editPaperStandardId = (string) $paper->standard_id;
        $this->editPaperFile       = null;
        $this->editPaperModalOpen  = true;
    }

    public function closeEditPaperModal(): void
    {
        $this->editPaperModalOpen = false;
        $this->reset(['editPaperId', 'editPaperTitle', 'editPaperStandardId', 'editPaperFile']);
    }

    public function saveEditPaper(): void
    {
        $this->validate([
            'editPaperStandardId' => 'required|exists:standards,id',
            'editPaperTitle'      => 'required|string|max:255',
            'editPaperFile'       => 'nullable|file|mimes:pdf|max:1024', // 1 MB
        ]);

        try {
            $paper = AdmissionExamPaper::where('id', $this->editPaperId)
                ->where('organization_id', $this->orgId())
                ->firstOrFail();

            $updateData = [
                'standard_id' => $this->editPaperStandardId,
                'title'       => $this->editPaperTitle,
            ];

            if ($this->editPaperFile) {
                if ($paper->file_path) {
                    Storage::disk('s3')->delete($paper->file_path);
                }
                $updateData['file_path'] = $this->editPaperFile->store(
                    'admin/admissions/exam-papers/' . $this->orgId(), 's3'
                );
            }

            $paper->update($updateData);
            $this->notification()->success('Paper updated successfully!');
            $this->closeEditPaperModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    public function saveExamPaper(): void
    {
        $this->validate([
            'paperStandardId' => 'required|exists:standards,id',
            'paperTitle'      => 'required|string|max:255',
            'paperFile'       => 'required|file|mimes:pdf|max:1024', // 1 MB
        ]);

        try {
            $path = $this->paperFile->store(
                'admin/admissions/exam-papers/' . $this->orgId(),
                's3'
            );

            AdmissionExamPaper::create([
                'organization_id' => $this->orgId(),
                'standard_id'     => $this->paperStandardId,
                'title'           => $this->paperTitle,
                'file_path'       => $path,
            ]);

            $this->notification()->success('Exam paper uploaded successfully!');
            $this->closePaperModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    public function deleteExamPaper(int $id): void { $this->pendingDeletePaperId = $id; }
    public function cancelDeletePaper(): void      { $this->pendingDeletePaperId = null; }

    public function doDeleteExamPaper(): void
    {
        $paper = AdmissionExamPaper::where('id', $this->pendingDeletePaperId)
            ->where('organization_id', $this->orgId())
            ->first();

        if ($paper) {
            if ($paper->file_path) {
                Storage::disk('s3')->delete($paper->file_path);
            }
            $paper->delete();
            $this->notification()->success('Exam paper deleted!');
        }
        $this->pendingDeletePaperId = null;
    }

    public function downloadExamPaper(int $id): mixed
    {
        $paper = AdmissionExamPaper::where('id', $id)
            ->where('organization_id', $this->orgId())
            ->first();

        if (!$paper || !$paper->file_path) {
            $this->notification()->error('Paper file not found.');
            return null;
        }

        // Guard against stale records whose S3 object no longer exists
        if (!Storage::disk('s3')->exists($paper->file_path)) {
            $this->notification()->error('File missing on storage. Please re-upload this exam paper.');
            return null;
        }

        // Force-download (Content-Disposition: attachment) — never opens in browser
        $filename = ($paper->title ?: 'exam-paper') . '.pdf';
        $url = Storage::disk('s3')->temporaryUrl(
            $paper->file_path,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
                'ResponseContentType'        => 'application/octet-stream',
            ]
        );
        return $this->redirect($url);
    }

    public function downloadResultPdf(int $id): mixed
    {
        $enquiry = AdmissionEnquiry::where('id', $id)
            ->where('organization_id', $this->orgId())
            ->first();

        if (!$enquiry || !$enquiry->result_pdf) {
            $this->notification()->error('PDF not found.');
            return null;
        }

        if (!Storage::disk('s3')->exists($enquiry->result_pdf)) {
            $this->notification()->error('File missing on storage. Please re-upload the result PDF.');
            return null;
        }

        $filename = 'result-' . ($enquiry->student_name ?: 'student') . '.pdf';
        $url = Storage::disk('s3')->temporaryUrl(
            $enquiry->result_pdf,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
                'ResponseContentType'        => 'application/octet-stream',
            ]
        );
        return $this->redirect($url);
    }

    // ─── Admission form PDF ──────────────────────────────────────────────────

    /**
     * Generate a clean, printable admission form for one enquiry — school
     * details, the student's details, fee summary and instructions — and stream
     * it to the browser as a download.
     */
    public function downloadAdmissionForm(int $id): mixed
    {
        $enquiry = AdmissionEnquiry::with('standard:id,name')
            ->where('id', $id)
            ->where('organization_id', $this->orgId())
            ->first();

        if (!$enquiry) {
            $this->notification()->error('Not found', 'Admission enquiry not found.');
            return null;
        }

        $org = Organization::find($this->orgId());

        $instructions = [
            'Please fill in all details in capital letters and verify them before submission.',
            'Attach self-attested copies of the birth certificate, previous mark-sheet and transfer certificate (if any).',
            'Attach two recent passport-size photographs of the student.',
            'The admission fee once paid is non-refundable and non-transferable.',
            'Admission is confirmed only after successful verification of documents and payment of fees.',
            'The school reserves the right to accept or reject any admission application.',
        ];

        $pdf = Pdf::loadView('pdf.admission-form', compact('enquiry', 'org', 'instructions'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'admission-form-' . str_replace(' ', '-', strtolower($enquiry->student_name ?: 'student')) . '.pdf';

        return response()->streamDownload(fn() => print($pdf->output()), $filename);
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);

        // Analytics in one aggregate query
        $stats = AdmissionEnquiry::where('organization_id', $orgId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "updated" THEN 1 ELSE 0 END) as updated,
                SUM(CASE WHEN YEAR(created_at) = ? AND MONTH(created_at) = ? THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN YEAR(created_at) = ? AND MONTH(created_at) = ? THEN 1 ELSE 0 END) as last_month
            ', [
                now()->year, now()->month,
                now()->subMonth()->year, now()->subMonth()->month,
            ])
            ->first();

        $analytics = [
            'total'      => (int) ($stats->total ?? 0),
            'updated'    => (int) ($stats->updated ?? 0),
            'this_month' => (int) ($stats->this_month ?? 0),
            'last_month' => (int) ($stats->last_month ?? 0),
        ];

        $enquiries = AdmissionEnquiry::with('standard:id,name')
            ->where('organization_id', $orgId)
            ->when($this->search, fn($q) => $q->where(fn($s) =>
                $s->where('student_name', 'like', "%{$this->search}%")
                  ->orWhere('mobile', 'like', "%{$this->search}%")
                  ->orWhere('guardian_name', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStandard, fn($q) => $q->where('standard_id', $this->filterStandard))
            ->when($this->filterMonth, function ($q) {
                $parts = explode('-', $this->filterMonth);
                if (count($parts) === 2) {
                    $q->whereYear('created_at', $parts[0])
                      ->whereMonth('created_at', $parts[1]);
                }
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $examPapers = AdmissionExamPaper::with('standard:id,name')
            ->where('organization_id', $orgId)
            ->when($this->filterPaperStandard, fn($q) => $q->where('standard_id', $this->filterPaperStandard))
            ->orderByDesc('created_at')
            ->get();

        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $monthOptions[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
            ];
        }

        return view('livewire.admin.admissions', [
            'standards'    => $standards,
            'enquiries'    => $enquiries,
            'examPapers'   => $examPapers,
            'analytics'    => $analytics,
            'monthOptions' => $monthOptions,
        ]);
    }
}
