<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\Admin\RateLms;
use App\Models\Organization;
use Livewire\WithPagination;
use Carbon\Carbon;

class Rating extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $ratingFilter = '';
    public $selectedReviewId = null;

    // Status constants
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_ARCHIVED = 3;

    public function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => ['label' => 'Active', 'color' => 'green'],
            self::STATUS_PENDING => ['label' => 'Pending', 'color' => 'yellow'],
            self::STATUS_ARCHIVED => ['label' => 'Archived', 'color' => 'gray'],
        ];
    }

    public function getStatusLabel($status)
    {
        $statuses = $this->getStatuses();
        return $statuses[$status]['label'] ?? 'Unknown';
    }

    public function getStatusColor($status)
    {
        $statuses = $this->getStatuses();
        return $statuses[$status]['color'] ?? 'gray';
    }

    // Format date to Indian timezone
    public function getIndianDate($date)
    {
        return Carbon::parse($date)
            ->timezone('Asia/Kolkata')
            ->format('d M Y, h:i A');
    }

    public function render()
    {
        $reviews = RateLms::with('organization')
            ->when($this->search, function ($query) {
                $query->whereHas('organization', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->ratingFilter, function ($query) {
                $query->where('rating', $this->ratingFilter);
            })
            ->latest()
            ->paginate(25);

        $stats = [
            'total_reviews' => RateLms::count(),
            'average_rating' => RateLms::avg('rating') ?? 0,
            'five_star' => RateLms::where('rating', 5)->count(),
            'active_schools_with_reviews' => RateLms::distinct('organization_id')->count('organization_id'),
            'active_reviews' => RateLms::where('status', self::STATUS_ACTIVE)->count(),
            'pending_reviews' => RateLms::where('status', self::STATUS_PENDING)->count(),
        ];

        $selectedReview = $this->selectedReviewId
            ? RateLms::with('organization')->find($this->selectedReviewId)
            : null;

        return view('livewire.super-admin.rating', compact('reviews', 'stats', 'selectedReview'));
    }

    public function viewReview($id)
    {
        $this->selectedReviewId = $id;
    }

    public function closeReview()
    {
        $this->selectedReviewId = null;
    }

    public function updateStatus($id, $status)
    {
        $review = RateLms::find($id);
        if ($review) {
            $review->update(['status' => $status]);
            $this->dispatch('notify', type: 'success', message: 'Review status updated successfully!');
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'ratingFilter']);
        $this->resetPage();
    }
}
