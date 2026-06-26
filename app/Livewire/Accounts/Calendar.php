<?php

namespace App\Livewire\Accounts;

use App\Models\Calendar\TimeTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Calendar extends Component
{
    public int $currentYear;
    public int $currentMonth;
    public ?array $selectedEvent = null;
    public bool $showEventModal = false;
    public ?string $selectedDayDate = null;
    public bool $showDayEventsModal = false;
    public array $selectedDayEvents = [];

    public function mount()
    {
        $now = Carbon::now();
        $this->currentYear = $now->year;
        $this->currentMonth = $now->month;
    }

    public function goToPreviousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->resetModals();
    }

    public function goToNextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->resetModals();
    }

    public function goToCurrentMonth(): void
    {
        $now = Carbon::now();
        $this->currentYear = $now->year;
        $this->currentMonth = $now->month;
        $this->resetModals();
    }

    public function viewEvent(int $eventId): void
    {
        $organizationId = Auth::user()->organization_id;

        $event = TimeTable::with([
            'academic.standard',
            'academic.section',
            'academic.subject',
            'academic.teacher',
            'location',
        ])
            ->where('organization_id', $organizationId)
            ->where('is_cancelled', false)
            ->find($eventId);

        if (!$event) {
            return;
        }

        $this->selectedEvent = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date' => $event->date->format('Y-m-d'),
            'start_time' => $event->start_time?->format('h:i A'),
            'end_time' => $event->end_time?->format('h:i A'),
            'is_all_day' => $event->is_all_day,
            'color' => $event->color ?? $this->getEventColor($event->event_type),
            'event_type' => $event->event_type,
            'location' => $event->location?->location_display,
            'standard' => $event->academic?->standard?->name,
            'section' => $event->academic?->section?->name,
            'subject' => $event->academic?->subject?->name,
            'teacher' => $event->academic?->teacher?->name,
        ];

        $this->showDayEventsModal = false;
        $this->showEventModal = true;
    }

    public function viewDayEvents(string $date): void
    {
        $organizationId = Auth::user()->organization_id;
        $carbonDate = Carbon::parse($date);

        $events = TimeTable::with(['academic.standard', 'academic.section', 'location'])
            ->where('organization_id', $organizationId)
            ->where('is_cancelled', false)
            ->whereDate('date', $carbonDate)
            ->orderBy('start_time')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_time' => $event->start_time?->format('h:i A'),
                    'end_time' => $event->end_time?->format('h:i A'),
                    'is_all_day' => $event->is_all_day,
                    'color' => $event->color ?? $this->getEventColor($event->event_type),
                    'event_type' => $event->event_type,
                    'location' => $event->location?->location_display,
                    'class' => $event->academic?->standard?->name
                        . ($event->academic?->section?->name ? ' - ' . $event->academic->section->name : ''),
                ];
            })
            ->toArray();

        if (empty($events)) {
            return;
        }

        $this->selectedDayDate = $carbonDate->format('l, F d, Y');
        $this->selectedDayEvents = $events;
        $this->showEventModal = false;
        $this->showDayEventsModal = true;
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->selectedEvent = null;
    }

    public function closeDayEventsModal(): void
    {
        $this->showDayEventsModal = false;
        $this->selectedDayEvents = [];
        $this->selectedDayDate = null;
    }

    private function resetModals(): void
    {
        $this->showEventModal = false;
        $this->showDayEventsModal = false;
        $this->selectedEvent = null;
        $this->selectedDayEvents = [];
        $this->selectedDayDate = null;
    }

    #[Computed]
    public function calendarGrid(): array
    {
        $firstOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth();

        // Start from Sunday of the first week
        $gridStart = $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        // End at Saturday of the last week
        $gridEnd = $lastOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $organizationId = Auth::user()->organization_id;

        // Fetch all events for the grid range
        $events = TimeTable::where('organization_id', $organizationId)
            ->where('is_cancelled', false)
            ->whereBetween('date', [$gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d')])
            ->select('id', 'title', 'date', 'start_time', 'end_time', 'is_all_day', 'color', 'event_type')
            ->orderBy('start_time')
            ->get();

        $weeks = [];
        $current = $gridStart->copy();

        while ($current->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $current->format('Y-m-d');
                $dayEvents = $events->filter(fn($e) => $e->date->format('Y-m-d') === $dateStr)
                    ->map(fn($e) => [
                        'id' => $e->id,
                        'title' => $e->title,
                        'color' => $e->color ?? $this->getEventColor($e->event_type),
                        'start_time' => $e->start_time?->format('h:i A'),
                        'is_all_day' => $e->is_all_day,
                    ])
                    ->values()
                    ->toArray();

                $week[] = [
                    'date' => $dateStr,
                    'day' => $current->day,
                    'isCurrentMonth' => $current->month === $this->currentMonth,
                    'isToday' => $current->isToday(),
                    'events' => $dayEvents,
                    'eventCount' => count($dayEvents),
                ];

                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /** Upcoming events within the selected month (today onward). */
    #[Computed]
    public function upcomingEvents(): array
    {
        return $this->monthEvents(completed: false);
    }

    /** Events within the selected month that have already passed. */
    #[Computed]
    public function completedEvents(): array
    {
        return $this->monthEvents(completed: true);
    }

    /**
     * Events for the currently selected month, split by whether they are in
     * the past (completed) or still upcoming relative to today.
     */
    private function monthEvents(bool $completed): array
    {
        $organizationId = Auth::user()->organization_id;
        $today = Carbon::today();

        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $query = TimeTable::with(['academic.standard', 'academic.section', 'location'])
            ->where('organization_id', $organizationId)
            ->where('is_cancelled', false)
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);

        if ($completed) {
            $query->whereDate('date', '<', $today)
                ->orderByDesc('date')
                ->orderBy('start_time');
        } else {
            $query->whereDate('date', '>=', $today)
                ->orderBy('date')
                ->orderBy('start_time');
        }

        return $query->get()
            ->map(fn($event) => $this->mapEventForList($event))
            ->toArray();
    }

    private function mapEventForList($event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date' => $event->date->format('Y-m-d'),
            'date_formatted' => $event->date->format('D, M d'),
            'start_time' => $event->start_time?->format('h:i A'),
            'is_all_day' => $event->is_all_day,
            'color' => $event->color ?? $this->getEventColor($event->event_type),
            'event_type' => $event->event_type,
            'location' => $event->location?->location_display,
            'class' => $event->academic?->standard?->name
                . ($event->academic?->section?->name ? ' - ' . $event->academic->section->name : ''),
        ];
    }

    #[Computed]
    public function eventsCount(): array
    {
        $organizationId = Auth::user()->organization_id;
        $today = Carbon::today();

        $baseQuery = fn() => TimeTable::where('organization_id', $organizationId)
            ->where('is_cancelled', false);

        return [
            'today' => $baseQuery()->whereDate('date', $today)->count(),
            'this_week' => $baseQuery()->whereBetween('date', [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek(),
            ])->count(),
            'this_month' => $baseQuery()->whereBetween('date', [
                Carbon::create($this->currentYear, $this->currentMonth, 1),
                Carbon::create($this->currentYear, $this->currentMonth, 1)->endOfMonth(),
            ])->count(),
            'this_year' => $baseQuery()->whereBetween('date', [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
            ])->count(),
        ];
    }

    #[Computed]
    public function monthLabel(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    private function getEventColor(?string $eventType = null): string
    {
        return match ($eventType) {
            'class' => '#3b82f6',
            'exam' => '#ef4444',
            'meeting' => '#f59e0b',
            'event' => '#10b981',
            'holiday' => '#8b5cf6',
            default => '#6b7280',
        };
    }

    public function render()
    {
        return view('livewire.accounts.calendar');
    }
}
