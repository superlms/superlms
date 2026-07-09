<?php

namespace App\Livewire\Components;

use App\Models\Admin\SchoolInfo as AdminSchoolInfo;
use App\Models\Admin\SchoolDocument;
use App\Models\Organization;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use WireUi\Traits\WireUiActions;

class Profile extends Component
{
    use WithFileUploads, WireUiActions;

    public $organization;
    public $currentPassword;
    public $showCurrentPassword = false;
    public $newPassword;
    public $confirmPassword;
    public $showPassword = false;
    public $photo;
    public $tempPhotoUrl;

    /** 'profile' (descriptive info) or 'info' (one card: bank + details + password). */
    public $activeTab = 'profile';

    /** When on the 'info' tab: 'view' (default, read-only card) or 'edit' (form). */
    public string $infoMode = 'view';

    /** Slide-in panel toggles (same UI template as member / document panels). */
    public bool $showLogoPanel      = false;
    public bool $showPasswordPanel  = false;

    public $schoolInfo;
    public $showNewPassword = false;
    public $showConfirmPassword = false;

    // School Info Fields
    public $aboutSchool;
    public $websiteInfo;
    public $websiteUrl;
    public $schoolEmail;
    public $schoolMobileNo;
    public $schoolAddress;
    public $schoolManagement = [];
    public $pendingDocuments = [];
    public $uploadedDocuments = [];
    public $schoolDocumentsText;
    public $managementPhoto;

    // USM Parameters
    public $usmVision;
    public $usmMission;
    public $usmValues;
    public $usmGoals;

    // Extra custom sections (admin can add/remove). Each item is ['title' => '', 'description' => ''].
    public array $customSections = [];
    public $pendingDeleteSectionIndex = null;

    // ─── Slide-in panel state ────────────────────────────────────────────────
    public bool  $showMemberPanel = false;
    public array $newMember       = [];
    public       $newMemberPhoto;
    public       $editMemberIndex = null;

    public bool  $showDocumentPanel = false;
    public array $newDocument       = [];
    public       $newDocumentFile;

    // ─── Delete confirms ─────────────────────────────────────────────────────
    public $pendingDeleteMemberIndex = null;
    public $pendingDeleteDocumentId  = null;

    public function mount()
    {
        $this->organization = Organization::with('schoolInfo')
            ->where('id', Auth::user()->organization_id)
            ->first();
        $this->loadSchoolInfo();
    }

    public function loadSchoolInfo()
    {
        $this->schoolInfo = AdminSchoolInfo::where('organization_id', Auth::user()->organization_id)->first();

        if ($this->schoolInfo) {
            $this->aboutSchool = $this->schoolInfo->about_school;
            $this->websiteInfo = $this->schoolInfo->website_info;
            $this->websiteUrl = $this->schoolInfo->website_url;
            $this->schoolEmail = $this->schoolInfo->school_email;
            $this->schoolMobileNo = $this->schoolInfo->school_mobile;
            $this->schoolAddress = $this->schoolInfo->school_address;
            $this->schoolManagement = $this->schoolInfo->managementTeam->toArray();
            $this->schoolDocumentsText = $this->schoolInfo->school_document_text;
            $this->uploadedDocuments = $this->schoolInfo->documents->toArray();

            $this->usmVision = $this->schoolInfo->usm_vision;
            $this->usmMission = $this->schoolInfo->usm_mission;
            $this->usmValues = $this->schoolInfo->usm_values;
            $this->usmGoals = $this->schoolInfo->usm_goals;
            $this->customSections = array_values(array_filter(
                (array) ($this->schoolInfo->custom_sections ?? []),
                fn ($s) => is_array($s)
            ));
        } else {
            $this->schoolManagement = [];
            $this->customSections = [];
        }
    }

    // ─── Custom Sections (extra paragraph cards) ─────────────────────────────

    public function addCustomSection(): void
    {
        $this->customSections[] = ['title' => '', 'description' => ''];
    }

    public function confirmDeleteSection($index): void
    {
        $this->pendingDeleteSectionIndex = (int) $index;
    }

    public function cancelDeleteSection(): void
    {
        $this->pendingDeleteSectionIndex = null;
    }

    public function executeDeleteSection(): void
    {
        if ($this->pendingDeleteSectionIndex !== null && isset($this->customSections[$this->pendingDeleteSectionIndex])) {
            unset($this->customSections[$this->pendingDeleteSectionIndex]);
            $this->customSections = array_values($this->customSections);
            $this->notification()->success('Section removed. Click "Save All Changes" to persist.');
        }
        $this->pendingDeleteSectionIndex = null;
    }

    // ─── Management Member Panel ─────────────────────────────────────────────

    public function openMemberPanel($index = null): void
    {
        $this->resetErrorBag();
        $this->editMemberIndex = $index;
        $this->newMember = $index !== null
            ? [
                'name'        => $this->schoolManagement[$index]['name'] ?? '',
                'designation' => $this->schoolManagement[$index]['designation'] ?? '',
                'photo_path'  => $this->schoolManagement[$index]['photo_path'] ?? null,
            ]
            : ['name' => '', 'designation' => '', 'photo_path' => null];
        $this->newMemberPhoto = null;
        $this->showMemberPanel = true;
    }

    public function closeMemberPanel(): void
    {
        $this->showMemberPanel = false;
        $this->editMemberIndex = null;
        $this->newMember = [];
        $this->newMemberPhoto = null;
    }

    public function saveMember(): void
    {
        $this->validate([
            'newMember.name'        => 'required|string|max:255',
            'newMember.designation' => 'required|string|max:255',
            'newMemberPhoto'        => 'nullable|image|max:2048',
        ], [], [
            'newMember.name'        => 'Member name',
            'newMember.designation' => 'Designation',
            'newMemberPhoto'        => 'Photo',
        ]);

        $member = [
            'name'        => $this->newMember['name'],
            'designation' => $this->newMember['designation'],
            'photo_path'  => $this->newMember['photo_path'] ?? null,
        ];

        if ($this->newMemberPhoto instanceof TemporaryUploadedFile) {
            // delete old image when editing
            if ($this->editMemberIndex !== null && !empty($this->schoolManagement[$this->editMemberIndex]['photo_path'])) {
                $oldPath = parse_url($this->schoolManagement[$this->editMemberIndex]['photo_path'], PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($oldPath, '/'));
            }
            $path = $this->newMemberPhoto->store('admin/school-management/photos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $member['photo_path'] = Storage::disk('s3')->url($path);
        }

        if ($this->editMemberIndex !== null) {
            $this->schoolManagement[$this->editMemberIndex] = $member;
        } else {
            $this->schoolManagement[] = $member;
        }

        $this->closeMemberPanel();
        $this->notification()->success('Member saved. Click "Save All Changes" to persist.');
    }

    public function confirmDeleteMember($index): void
    {
        $this->pendingDeleteMemberIndex = $index;
    }

    public function cancelDeleteMember(): void
    {
        $this->pendingDeleteMemberIndex = null;
    }

    public function executeDeleteMember(): void
    {
        if ($this->pendingDeleteMemberIndex !== null && isset($this->schoolManagement[$this->pendingDeleteMemberIndex])) {
            if (!empty($this->schoolManagement[$this->pendingDeleteMemberIndex]['photo_path'])) {
                $oldPath = parse_url($this->schoolManagement[$this->pendingDeleteMemberIndex]['photo_path'], PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($oldPath, '/'));
            }
            unset($this->schoolManagement[$this->pendingDeleteMemberIndex]);
            $this->schoolManagement = array_values($this->schoolManagement);
            $this->notification()->success('Member removed. Click "Save All Changes" to persist.');
        }
        $this->pendingDeleteMemberIndex = null;
    }

    // ─── Document Panel ──────────────────────────────────────────────────────

    public function openDocumentPanel(): void
    {
        $this->resetErrorBag();
        $this->newDocument = ['title' => ''];
        $this->newDocumentFile = null;
        $this->showDocumentPanel = true;
    }

    public function closeDocumentPanel(): void
    {
        $this->showDocumentPanel = false;
        $this->newDocument = [];
        $this->newDocumentFile = null;
    }

    public function saveDocumentPanel(): void
    {
        $this->validate([
            'newDocument.title' => 'required|string|max:255',
            'newDocumentFile'   => 'required|file|mimes:pdf|max:2048',
        ], [
            'newDocumentFile.mimes' => 'Document must be a PDF file.',
            'newDocumentFile.max'   => 'Document must not exceed 2 MB.',
        ], [
            'newDocument.title' => 'Title',
            'newDocumentFile'   => 'Document file',
        ]);

        // Queue as pending — saved to DB only on saveSchoolInfo()
        $this->pendingDocuments[] = [
            'file'  => $this->newDocumentFile,
            'title' => $this->newDocument['title'],
        ];

        $this->closeDocumentPanel();
        $this->notification()->success('Document queued. Click "Save All Changes" to upload.');
    }

    public function removePendingDocument($index): void
    {
        unset($this->pendingDocuments[$index]);
        $this->pendingDocuments = array_values($this->pendingDocuments);
    }

    public function confirmDeleteDocument($id): void
    {
        $this->pendingDeleteDocumentId = $id;
    }

    public function cancelDeleteDocument(): void
    {
        $this->pendingDeleteDocumentId = null;
    }

    public function executeDeleteDocument(): void
    {
        if ($this->pendingDeleteDocumentId) {
            $document = SchoolDocument::find($this->pendingDeleteDocumentId);
            if ($document) {
                $filePath = parse_url($document->file_path, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($filePath, '/'));
                $document->delete();
                $this->loadSchoolInfo();
                $this->notification()->success('Document deleted.');
            }
        }
        $this->pendingDeleteDocumentId = null;
    }

    public function showTab($tab)
    {
        $this->activeTab = $tab;

        // Switching tabs always drops back to view (the form is a global
        // mode driven by $infoMode and is reached from either tab via
        // an Edit / Add button).
        $this->infoMode = 'view';
        $this->showPasswordPanel = false;
    }

    public function setInfoMode(string $mode): void
    {
        $this->infoMode = in_array($mode, ['view', 'edit'], true) ? $mode : 'view';
        $this->resetErrorBag();
    }

    public function openLogoPanel(): void
    {
        $this->reset('photo', 'tempPhotoUrl');
        $this->resetErrorBag('photo');
        $this->showLogoPanel = true;
    }

    public function closeLogoPanel(): void
    {
        $this->reset('photo', 'tempPhotoUrl');
        $this->resetErrorBag('photo');
        $this->showLogoPanel = false;
    }

    public function openPasswordPanel(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->resetErrorBag();
        $this->showPasswordPanel = true;
    }

    public function closePasswordPanel(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->resetErrorBag();
        $this->showPasswordPanel = false;
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => ['image', 'max:2048'],
        ]);
        $this->tempPhotoUrl = $this->photo->temporaryUrl();
    }

    public function savePhoto()
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        if ($this->organization->logo) {
            $oldPhotoPath = parse_url($this->organization->logo, PHP_URL_PATH);
            Storage::disk('s3')->delete($oldPhotoPath);
        }

        $imagePath = $this->photo->store('admin/profile/photos', 's3');
        Storage::disk('s3')->setVisibility($imagePath, 'public');
        $imageUrl = Storage::disk('s3')->url($imagePath);

        $this->organization->update(['logo' => $imageUrl]);
        $this->organization->refresh();

        $this->reset('photo', 'tempPhotoUrl');
        $this->showLogoPanel = false;
        $this->notification()->success('School logo updated.');
    }

    public function togglePasswordVisibility($field)
    {
        if ($field === 'current') {
            $this->showCurrentPassword = !$this->showCurrentPassword;
        } elseif ($field === 'new') {
            $this->showNewPassword = !$this->showNewPassword;
        } elseif ($field === 'confirm') {
            $this->showConfirmPassword = !$this->showConfirmPassword;
        }
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => [
                'required',
                'different:currentPassword',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        Auth::user()->rememberPlainPassword($this->newPassword);
        Auth::user()->update([
            'password' => Hash::make($this->newPassword)
        ]);

        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->showPasswordPanel = false;
        $this->notification()->success('Password updated.');
    }

    public function addManagement()
    {
        $this->schoolManagement[] = [
            'name' => '',
            'designation' => '',
            'photo' => null
        ];
    }

    public function removeManagement($index)
    {
        if (!empty($this->schoolManagement[$index]['photo_path'])) {
            $oldPhotoPath = parse_url($this->schoolManagement[$index]['photo_path'], PHP_URL_PATH);
            Storage::disk('s3')->delete($oldPhotoPath);
        }

        unset($this->schoolManagement[$index]);
        $this->schoolManagement = array_values($this->schoolManagement);
        $this->notification()->success('Removed! Click Save to apply changes.');
    }

    public function updatedManagementPhoto($value, $index)
    {
        $this->validate([
            "schoolManagement.$index.photo" => 'image|max:2048',
        ]);
    }

    public function removeManagementPhoto($index)
    {
        if (isset($this->schoolManagement[$index]['photo']) && $this->schoolManagement[$index]['photo'] instanceof TemporaryUploadedFile) {
            $this->schoolManagement[$index]['photo'] = null;
        } elseif (!empty($this->schoolManagement[$index]['photo_path'])) {
            $oldPhotoPath = parse_url($this->schoolManagement[$index]['photo_path'], PHP_URL_PATH);
            Storage::disk('s3')->delete($oldPhotoPath);
            $this->schoolManagement[$index]['photo_path'] = null;
        }
    }

    public function addDocumentSlot()
    {
        $this->pendingDocuments[] = ['file' => null, 'title' => ''];
    }

    public function removeUploadedFile($index)
    {
        unset($this->pendingDocuments[$index]);
        $this->pendingDocuments = array_values($this->pendingDocuments);
    }

    public function saveSchoolInfo()
    {
        try {
            // Drop empty management rows so blank defaults don't fail required validation
            $this->schoolManagement = array_values(array_filter(
                $this->schoolManagement,
                fn($m) => !empty(trim($m['name'] ?? '')) || !empty(trim($m['designation'] ?? ''))
            ));

            $this->validate([
                'aboutSchool' => 'nullable|string',
                'websiteInfo' => 'nullable|string',
                'websiteUrl' => 'nullable|url:http,https',
                'schoolEmail' => 'nullable|email',
                'schoolMobileNo' => 'nullable|regex:/^[0-9]+$/|min:10|max:15',
                'schoolAddress' => 'nullable|string|max:255',
                'pendingDocuments.*.file' => 'nullable|file|mimes:pdf|max:2048',
                'pendingDocuments.*.title' => 'nullable|string|max:255',
                'schoolManagement.*.name' => 'required|string',
                'schoolManagement.*.designation' => 'required|string',
                'schoolManagement.*.photo' => 'nullable|image|max:2048',
                'schoolDocumentsText' => 'nullable|string',
                'usmVision' => 'nullable|string',
                'usmMission' => 'nullable|string',
                'usmValues' => 'nullable|string',
                'usmGoals' => 'nullable|string',
                'customSections' => 'nullable|array',
                'customSections.*.title' => 'nullable|string|max:255',
                'customSections.*.description' => 'nullable|string',
            ], [
                'pendingDocuments.*.file.mimes' => 'Document must be a PDF file.',
                'pendingDocuments.*.file.max' => 'Document must not exceed 2 MB.',
                'pendingDocuments.*.title.max' => 'Document title must not exceed 255 characters.',
                'schoolManagement.*.photo.max' => 'Management photo must not exceed 2 MB.',
                'schoolMobileNo.regex' => 'Mobile number must contain only digits.',
                'schoolMobileNo.min' => 'Mobile number must be at least 10 digits.',
                'schoolMobileNo.max' => 'Mobile number must not exceed 15 digits.',
                'schoolAddress.max' => 'School address must not exceed 255 characters.',
                'schoolManagement.*.name.required' => 'Management member name is required.',
                'schoolManagement.*.designation.required' => 'Management member designation is required.',
                'websiteUrl.url' => 'Website URL must start with http:// or https://.',
                'schoolEmail.email' => 'School email must be a valid email address.',
            ]);

            // Drop fully-empty custom sections so blank rows don't persist
            $cleanedSections = array_values(array_filter(
                $this->customSections,
                fn ($s) => !empty(trim($s['title'] ?? '')) || !empty(trim($s['description'] ?? ''))
            ));

            $schoolInfo = AdminSchoolInfo::updateOrCreate(
                ['organization_id' => $this->organization->id],
                [
                    'about_school' => $this->aboutSchool,
                    'website_info' => $this->websiteInfo,
                    'website_url' => $this->websiteUrl,
                    'school_email' => $this->schoolEmail,
                    'school_mobile' => $this->schoolMobileNo,
                    'school_address' => $this->schoolAddress,
                    'school_document_text' => $this->schoolDocumentsText,
                    'usm_vision' => $this->usmVision,
                    'usm_mission' => $this->usmMission,
                    'usm_values' => $this->usmValues,
                    'usm_goals' => $this->usmGoals,
                    'custom_sections' => $cleanedSections,
                ]
            );

            // Process management team
            $schoolInfo->managementTeam()->delete();
            foreach ($this->schoolManagement as $index => $member) {
                $memberData = [
                    'name' => $member['name'],
                    'designation' => $member['designation'],
                    'sort_order' => $index,
                ];

                if (isset($member['photo']) && $member['photo'] instanceof TemporaryUploadedFile) {
                    $photoPath = $member['photo']->store('admin/school-management/photos', 's3');
                    Storage::disk('s3')->setVisibility($photoPath, 'public');
                    $memberData['photo_path'] = Storage::disk('s3')->url($photoPath);
                } elseif (!empty($member['photo_path'])) {
                    $memberData['photo_path'] = $member['photo_path'];
                }

                $schoolInfo->managementTeam()->create($memberData);
            }

            // Process pending document uploads (each slot is independent — no index mismatch)
            foreach ($this->pendingDocuments as $index => $pending) {
                if (empty($pending['file']) || !($pending['file'] instanceof TemporaryUploadedFile)) {
                    continue;
                }
                $filePath = $pending['file']->store('admin/school-documents', 's3');
                Storage::disk('s3')->setVisibility($filePath, 'public');

                $schoolInfo->documents()->create([
                    'title' => trim($pending['title']) ?: $pending['file']->getClientOriginalName(),
                    'file_path' => Storage::disk('s3')->url($filePath),
                    'file_type' => $pending['file']->getClientOriginalExtension(),
                    'sort_order' => count($this->uploadedDocuments) + $index,
                ]);
            }

            $this->notification()->success('School information saved.');
            $this->loadSchoolInfo();
            $this->pendingDocuments = [];
            // Land back in view mode on the descriptive tab so the user
            // sees the freshly-saved content.
            $this->activeTab = 'info';
            $this->infoMode  = 'view';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->flatten()->implode('<br>');
            $this->notification()->error('Please fix the following errors:<br>' . $errors);
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }
    }

    public function removeDocument($id)
    {
        $document = SchoolDocument::find($id);
        if ($document) {
            $filePath = parse_url($document->file_path, PHP_URL_PATH);
            Storage::disk('s3')->delete($filePath);
            $document->delete();
            $this->loadSchoolInfo();
        }
    }

    public function render()
    {
        return view('livewire.components.profile');
    }
}
