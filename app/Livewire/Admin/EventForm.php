<?php

namespace App\Livewire\Admin;

use App\Models\Calendar\TimeTable;
use App\Models\Calendar\TimeTableAcademic;
use App\Models\Calendar\TimeTableLocation;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class EventForm extends Component
{
    use WireUiActions, WithFileUploads;

    public $date;
    public $event;
    public $mode = 'create';

    // Form fields
    public $title = '';
    public $description = '';
    // Optional image/PDF attachment (≤ 1 MB)
    public $attachment;
    public $event_date;
    public $start_time = '';
    public $end_time = '';
    public $event_type = 'class';
    public $color = '#3b82f6';
    public $is_all_day = false;
    
    // Academic details
    public $standard_id = '';
    public $section_id = '';
    public $subject_id = '';
    public $teacher_detail_id = '';
    
    // Location
    public $room_number = '';
    public $building = '';
    public $location = '';
    
    // Options
    public $standards = [];
    public $sections = [];
    public $subjects = [];
    public $teachers = [];

    protected $rules = [
        'title' => 'required|string|max:1000',
        'description' => 'nullable|string|max:3000',
        'event_date' => 'required|date',
        'start_time' => 'required_if:is_all_day,false',
        'end_time' => 'required_if:is_all_day,false',
        // Must stay in sync with the time_tables.event_type enum.
        'event_type' => 'required|in:class,lab,meeting,seminar,workshop,sports,exam,holiday,conference,event,other',
        'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:1024', // 1 MB
    ];

    protected $messages = [
        'title.max'       => 'Title may not be longer than 1000 characters.',
        'description.max' => 'Description may not be longer than 3000 characters.',
        'attachment.max'  => 'Attachment must be 1 MB (1024 KB) or smaller.',
        'attachment.mimes' => 'Attachment must be an image or PDF.',
    ];

    public function mount($date = null, $event = null, $mode = 'create')
    {
        $this->mode = $mode;
        $this->loadOptions();
        
        if ($date) {
            $this->event_date = $date;
        } else {
            $this->event_date = now()->format('Y-m-d');
        }
        
        if ($event) {
            $this->event = $event;
            $this->loadEventData($event);
        }
    }

    public function loadOptions()
    {
        $organizationId = Auth::user()->organization_id;
        
        $this->standards = Standard::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();
            
        $this->teachers = User::where('organization_id', $organizationId)
            ->where('role', 'teacher')
            ->get();
    }

    public function updatedStandardId($value)
    {
        if ($value) {
            $this->sections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->get();
        } else {
            $this->sections = [];
            $this->section_id = '';
        }
        $this->subjects = [];
        $this->subject_id = '';
    }

    public function updatedSectionId($value)
    {
        if ($value && $this->standard_id) {
            $this->subjects = Subject::where('standard_id', $this->standard_id)
                ->where('is_active', true)
                ->get();
        } else {
            $this->subjects = [];
            $this->subject_id = '';
        }
    }

    public function loadEventData($event)
    {
        $this->title = $event->title;
        $this->description = $event->description;
        $this->event_date = $event->date->format('Y-m-d');
        $this->start_time = $event->start_time?->format('H:i');
        $this->end_time = $event->end_time?->format('H:i');
        $this->event_type = $event->event_type;
        $this->color = $event->color;
        $this->is_all_day = $event->is_all_day;
        
        if ($event->academic) {
            $this->standard_id = $event->academic->standard_id;
            $this->section_id = $event->academic->section_id;
            $this->subject_id = $event->academic->subject_id;
            $this->teacher_detail_id = $event->academic->teacher_detail_id;
            
            if ($this->standard_id) {
                $this->sections = Section::where('standard_id', $this->standard_id)->get();
                $this->subjects = Subject::where('standard_id', $this->standard_id)->get();
            }
        }
        
        if ($event->location) {
            $this->room_number = $event->location->room_number;
            $this->building = $event->location->building;
            $this->location = $event->location->location;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $organizationId = Auth::user()->organization_id;

            // The attachment column is added by a migration; guard the write so a
            // DB that hasn't run it yet keeps working (same pattern as Teacher).
            $hasAttachmentCol = Schema::hasColumn('time_tables', 'attachment');

            $payload = [
                'title' => $this->title,
                'description' => $this->description,
                'date' => $this->event_date,
                'start_time' => $this->is_all_day ? null : $this->start_time,
                'end_time' => $this->is_all_day ? null : $this->end_time,
                'event_type' => $this->event_type,
                'color' => $this->color,
                'is_all_day' => $this->is_all_day,
            ];

            // Upload a freshly-chosen attachment and drop the previous one.
            if ($hasAttachmentCol && $this->attachment) {
                $existingUrl = $this->mode === 'create' ? null : ($this->event->attachment ?? null);
                if ($existingUrl) {
                    Storage::disk('s3')->delete(ltrim(parse_url($existingUrl, PHP_URL_PATH), '/'));
                }
                $path = $this->attachment->store('admin/calendar/attachments', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $payload['attachment'] = Storage::disk('s3')->url($path);
            }

            if ($this->mode === 'create') {
                $event = TimeTable::create(array_merge([
                    'organization_id' => $organizationId,
                    'created_by' => Auth::id(),
                ], $payload));
            } else {
                $event = TimeTable::find($this->event->id);
                $event->update($payload);
            }

            // Save academic details
            if ($this->standard_id || $this->teacher_detail_id) {
                TimeTableAcademic::updateOrCreate(
                    ['time_table_id' => $event->id],
                    [
                        'organization_id' => $organizationId,
                        'standard_id' => $this->standard_id ?? 0, 
                        'section_id' => $this->section_id ?? 0,
                        'subject_id' => $this->subject_id ?? 0,
                        'teacher_detail_id' => $this->teacher_detail_id ?? 0,
                    ]
                );
            }

            // Save location
            if ($this->room_number || $this->building || $this->location) {
                TimeTableLocation::updateOrCreate(
                    ['time_table_id' => $event->id],
                    [
                        'organization_id' => $organizationId,
                        'room_number' => $this->room_number,
                        'building' => $this->building,
                        'location' => $this->location,
                    ]
                );
            }

            $this->notification()->success(
                $this->mode === 'create' ? 'Event Created' : 'Event Updated',
                $this->mode === 'create' ? 'Event has been created successfully.' : 'Event has been updated successfully.'
            );

            $this->dispatch('eventSaved');

        } catch (\Exception $e) {
            $this->notification()->error(
                'Error',
                'Failed to save event: ' . $e->getMessage()
            );
        }
    }

    public function deleteEvent()
    {
        $this->dialog()->confirm([
            'title' => 'Are you sure?',
            'description' => 'This event will be deleted permanently.',
            'icon' => 'error',
            'accept' => [
                'label' => 'Yes, delete it',
                'method' => 'performDelete',
            ],
            'reject' => [
                'label' => 'Cancel',
            ],
        ]);
    }

    public function performDelete()
    {
        try {
            $event = TimeTable::find($this->event->id);
            $event->delete();

            $this->notification()->success(
                'Event Deleted',
                'Event has been deleted successfully.'
            );

            $this->dispatch('eventSaved');

        } catch (\Exception $e) {
            $this->notification()->error(
                'Error',
                'Failed to delete event: ' . $e->getMessage()
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.event-form');
    }
}