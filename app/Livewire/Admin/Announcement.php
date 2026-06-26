<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Admin\Announcement as AnnouncementModel;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Storage;
use WireUi\Traits\WireUiActions;
use Carbon\Carbon;

class Announcement extends Component
{
    use WithPagination, WithFileUploads, WireUiActions;

    public $open = false;
    public $viewModal = false;
    public $editId = null;
    public $selectedAnnouncement = null;
    public $dateFilter = 'all';
    /** all | user (Student) | teacher */
    public $typeFilter = 'all';

    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    #[Rule('required|string|max:255')]
    public $announcementName = '';

    #[Rule('required|string')]
    public $announcementContent = '';

    #[Rule('required|in:all,user,teacher')]
    public $type = 'all';

    #[Rule('nullable|image|max:2048')] // 2MB max
    public $announcementImage;

    #[Rule('nullable|mimes:pdf|max:5120')] // 5MB max
    public $announcementPdf;

    /** Unified uploader — accepts an image (≤2 MB) or PDF (≤5 MB).
     *  On save we route the file into the correct legacy column. */
    #[Rule('nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120')]
    public $announcementFile;

    public function render()
    {
        // Best-effort opportunistic purge — every render trims rows older than 60d.
        // The console schedule does the same daily; this catches dev/preview
        // environments where the scheduler may not be running.
        $this->purgeOldAnnouncements(false);

        $query = AnnouncementModel::where('organization_id', Auth::user()->organization_id)->latest();

        if ($this->dateFilter !== 'all') {
            $days = (int) $this->dateFilter;
            $startDate = Carbon::now()->subDays($days);
            $query->where('created_at', '>=', $startDate);
        }

        if (in_array($this->typeFilter, ['all', 'user', 'teacher'], true) && $this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        $announcements = $query->paginate(10);

        // Stats
        $baseQuery = AnnouncementModel::where('organization_id', Auth::user()->organization_id);
        $stats = [
            'total'       => (clone $baseQuery)->count(),
            'this_month'  => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)->count(),
            'last_month'  => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)->count(),
        ];

        return view('livewire.admin.announcement', compact('announcements', 'stats'));
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->open = true;
        $this->resetForm();
    }

    public function viewAnnouncement($id)
    {
        $this->selectedAnnouncement = AnnouncementModel::findOrFail($id);
        $this->viewModal = true;
    }

    public function closeViewModal()
    {
        $this->viewModal = false;
        $this->selectedAnnouncement = null;
    }

    public function editFromView($id)
    {
        $this->viewModal = false;
        $this->edit($id);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'organization_id' => Auth::user()->organization_id,
            'user_id' => Auth::user()->id,
            'announcement_name' => $this->announcementName,
            'announcement_content' => $this->announcementContent,
            'type' => $this->type,
        ];

        $existing = $this->editId ? AnnouncementModel::find($this->editId) : null;

        // ── Unified uploader: detect image vs PDF and route into the right column ──
        if ($this->announcementFile) {
            $ext  = strtolower($this->announcementFile->getClientOriginalExtension());
            $mime = (string) $this->announcementFile->getMimeType();
            $isPdf = $ext === 'pdf' || $mime === 'application/pdf';

            if ($isPdf) {
                $pdfPath = $this->announcementFile->store('admin/announcements/pdfs', 's3');
                Storage::disk('s3')->setVisibility($pdfPath, 'public');
                $data['announcement_pdf'] = Storage::disk('s3')->url($pdfPath);

                // Replacing an existing PDF: drop the old S3 object
                if ($existing && $existing->announcement_pdf) {
                    $old = parse_url($existing->announcement_pdf, PHP_URL_PATH);
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            } else {
                $imagePath = $this->announcementFile->store('admin/announcements/images', 's3');
                Storage::disk('s3')->setVisibility($imagePath, 'public');
                $data['announcement_image'] = Storage::disk('s3')->url($imagePath);

                if ($existing && $existing->announcement_image) {
                    $old = parse_url($existing->announcement_image, PHP_URL_PATH);
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            }
        }

        // ── Legacy split fields (keep working if anything else still uses them) ──
        if ($this->announcementImage) {
            $imagePath = $this->announcementImage->store('admin/announcements/images', 's3');
            Storage::disk('s3')->setVisibility($imagePath, 'public');
            $data['announcement_image'] = Storage::disk('s3')->url($imagePath);

            if ($existing && $existing->announcement_image) {
                $old = parse_url($existing->announcement_image, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($old, '/'));
            }
        }

        if ($this->announcementPdf) {
            $pdfPath = $this->announcementPdf->store('admin/announcements/pdfs', 's3');
            Storage::disk('s3')->setVisibility($pdfPath, 'public');
            $data['announcement_pdf'] = Storage::disk('s3')->url($pdfPath);

            if ($existing && $existing->announcement_pdf) {
                $old = parse_url($existing->announcement_pdf, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($old, '/'));
            }
        }

        if ($existing) {
            $existing->update($data);
            $message = 'Announcement updated successfully!';
        } else {
            AnnouncementModel::create($data);
            $message = 'Announcement created successfully!';
        }

        $this->closeModal();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    /**
     * Hard-delete announcements older than 60 days for THIS organization,
     * cleaning up associated S3 files. Called opportunistically from render()
     * and on a daily schedule for guaranteed coverage.
     */
    protected function purgeOldAnnouncements(bool $notify = false): void
    {
        $cutoff = Carbon::now()->subDays(60);

        $stale = AnnouncementModel::where('organization_id', Auth::user()->organization_id)
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        foreach ($stale as $row) {
            if ($row->announcement_image) {
                $p = parse_url($row->announcement_image, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($p, '/'));
            }
            if ($row->announcement_pdf) {
                $p = parse_url($row->announcement_pdf, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($p, '/'));
            }
            $row->delete();
        }

        if ($notify) {
            $this->dispatch('notify', type: 'success', message: "Removed {$stale->count()} announcement(s) older than 60 days.");
        }
    }

    public function edit($id)
    {
        $announcement = AnnouncementModel::findOrFail($id);
        $this->editId = $id;
        $this->announcementName = $announcement->announcement_name;
        $this->announcementContent = $announcement->announcement_content;
        $this->type = $announcement->type;
        $this->open = true;
    }

    public function onDelete($id)
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        $announcement = AnnouncementModel::find($this->deleteTargetId);

        if ($announcement) {
            // Delete associated files from S3
            if ($announcement->announcement_image) {
                $oldImagePath = parse_url($announcement->announcement_image, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldImagePath);
            }

            if ($announcement->announcement_pdf) {
                $oldPdfPath = parse_url($announcement->announcement_pdf, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldPdfPath);
            }

            $announcement->delete();

            $this->dispatch('notify', type: 'success', message: "Announcement Deleted Successfully!");
        } else {
            $this->dispatch('notify', type: 'error', message: "Announcement not found!");
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function deleteFile($type)
    {
        if ($type === 'image') {
            if ($this->editId && $this->announcementImage) {
                $this->announcementImage = null;
            } elseif ($this->editId) {
                $announcement = AnnouncementModel::find($this->editId);
                if ($announcement->announcement_image) {
                    $oldImagePath = parse_url($announcement->announcement_image, PHP_URL_PATH);
                    Storage::disk('s3')->delete($oldImagePath);
                    $announcement->update(['announcement_image' => null]);
                }
            }
        } elseif ($type === 'pdf') {
            if ($this->editId && $this->announcementPdf) {
                $this->announcementPdf = null;
            } elseif ($this->editId) {
                $announcement = AnnouncementModel::find($this->editId);
                if ($announcement->announcement_pdf) {
                    $oldPdfPath = parse_url($announcement->announcement_pdf, PHP_URL_PATH);
                    Storage::disk('s3')->delete($oldPdfPath);
                    $announcement->update(['announcement_pdf' => null]);
                }
            }
        }
    }

    public function closeModal()
    {
        $this->open = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->reset([
            'editId',
            'announcementName',
            'announcementContent',
            'type',
            'announcementImage',
            'announcementPdf',
            'announcementFile',
        ]);
        $this->resetErrorBag();
    }
}
