<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Support extends Component
{
    use WithPagination, WireUiActions;

    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';
    public $filterDays = null;

    public $showDetailModal = false;
    public $showReplyModal = false;
    public $selectedSupport = null;
    public $selectedType = null;

    public $adminReply = '';

    public function mount() {}

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedFilterDays()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'typeFilter', 'statusFilter', 'filterDays']);
        $this->resetPage();
    }

    protected function getOrganizationId()
    {
        return auth()->user()->organization_id;
    }

    protected function applyCommonFilters($query, $queryField)
    {
        $orgId = $this->getOrganizationId();
        $query->where('organization_id', $orgId)->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) use ($queryField) {
                $q->where('topic', 'like', '%' . $this->search . '%')
                    ->orWhere($queryField, 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->statusFilter === 'pending') {
            $query->where('admin_reply', false);
        } elseif ($this->statusFilter === 'replied') {
            $query->where('admin_reply', true);
        }

        if ($this->filterDays) {
            $query->where('created_at', '>=', now()->subDays($this->filterDays));
        }

        return $query;
    }

    public function getSupportsProperty()
    {
        $orgId = $this->getOrganizationId();

        if ($this->typeFilter === 'student') {
            $studentQuery = $this->applyCommonFilters(
                ContactAdminStudent::with(['user', 'studentDetail']),
                'student_query'
            );

            $results = $studentQuery->get()->map(function ($item) {
                $item->_type = 'student';
                $item->_query = $item->student_query;
                return $item;
            });
        } elseif ($this->typeFilter === 'teacher') {
            $teacherQuery = $this->applyCommonFilters(
                ContactAdminTeacher::with(['user', 'teacherDetail']),
                'teacher_query'
            );

            $results = $teacherQuery->get()->map(function ($item) {
                $item->_type = 'teacher';
                $item->_query = $item->teacher_query;
                return $item;
            });
        } else {
            $students = $this->applyCommonFilters(
                ContactAdminStudent::with(['user', 'studentDetail']),
                'student_query'
            )->get()->map(function ($item) {
                $item->_type = 'student';
                $item->_query = $item->student_query;
                return $item;
            });

            $teachers = $this->applyCommonFilters(
                ContactAdminTeacher::with(['user', 'teacherDetail']),
                'teacher_query'
            )->get()->map(function ($item) {
                $item->_type = 'teacher';
                $item->_query = $item->teacher_query;
                return $item;
            });

            $results = $students->merge($teachers)->sortByDesc('created_at');
        }

        $page = $this->getPage();
        $perPage = 10;
        $items = $results->forPage($page, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $results->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function viewSupport($supportId, $type)
    {
        $this->selectedType = $type;

        if ($type === 'student') {
            $this->selectedSupport = ContactAdminStudent::with(['user', 'studentDetail'])->find($supportId);
        } else {
            $this->selectedSupport = ContactAdminTeacher::with(['user', 'teacherDetail'])->find($supportId);
        }

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSupport = null;
        $this->selectedType = null;
    }

    public function openReplyModal($supportId, $type)
    {
        $this->selectedType = $type;

        if ($type === 'student') {
            $this->selectedSupport = ContactAdminStudent::find($supportId);
        } else {
            $this->selectedSupport = ContactAdminTeacher::find($supportId);
        }

        $this->showReplyModal = true;
        $this->adminReply = $this->selectedSupport->admin_text ?? '';
    }

    public function closeReplyModal()
    {
        $this->showReplyModal = false;
        $this->selectedSupport = null;
        $this->selectedType = null;
        $this->adminReply = '';
    }

    public function sendReply()
    {
        $this->validate([
            'adminReply' => 'required|string|min:5',
        ]);

        try {
            $this->selectedSupport->update([
                'admin_text' => $this->adminReply,
                'admin_reply' => true,
            ]);

            $this->notification()->success(
                title: 'Reply Sent',
                description: 'Your reply has been sent successfully.'
            );

            $this->closeReplyModal();
            $this->closeDetailModal();
        } catch (\Exception $e) {
            $this->notification()->error(
                title: 'Error',
                description: 'Failed to send reply: ' . $e->getMessage()
            );
        }
    }

    public function deleteSupport($supportId, $type)
    {
        try {
            if ($type === 'student') {
                ContactAdminStudent::find($supportId)->delete();
            } else {
                ContactAdminTeacher::find($supportId)->delete();
            }

            $this->notification()->success(
                title: 'Deleted',
                description: 'Support ticket deleted successfully.'
            );

            if ($this->selectedSupport && $this->selectedSupport->id == $supportId) {
                $this->closeDetailModal();
                $this->closeReplyModal();
            }
        } catch (\Exception $e) {
            $this->notification()->error(
                title: 'Error',
                description: 'Failed to delete ticket: ' . $e->getMessage()
            );
        }
    }

    protected function getQueryText($support, $type)
    {
        return $type === 'student' ? $support->student_query : $support->teacher_query;
    }

    public function render()
    {
        $orgId = $this->getOrganizationId();

        $totalStudentQueries = ContactAdminStudent::where('organization_id', $orgId)->count();
        $totalTeacherQueries = ContactAdminTeacher::where('organization_id', $orgId)->count();
        $totalQueries = $totalStudentQueries + $totalTeacherQueries;

        $pendingQueries = ContactAdminStudent::where('organization_id', $orgId)->where('admin_reply', false)->count()
            + ContactAdminTeacher::where('organization_id', $orgId)->where('admin_reply', false)->count();

        $repliedQueries = ContactAdminStudent::where('organization_id', $orgId)->where('admin_reply', true)->count()
            + ContactAdminTeacher::where('organization_id', $orgId)->where('admin_reply', true)->count();

        return view('livewire.admin.support', [
            'supports'            => $this->supports,
            'totalQueries'        => $totalQueries,
            'pendingQueries'      => $pendingQueries,
            'repliedQueries'      => $repliedQueries,
            'totalStudentQueries' => $totalStudentQueries,
            'totalTeacherQueries' => $totalTeacherQueries,
        ]);
    }
}
