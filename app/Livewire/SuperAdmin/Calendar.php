<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Super Admin → Calendar: the signed-in person's own meetings (when, with
 * whom, where), tasks (ticked off when done) and events. A month on the left,
 * Today's list — and the day picked on the month — on the right, with the
 * tasks left open from earlier days.
 *
 * Every row is the signed-in user's own (user_id): the super admin and each
 * sub-super-admin given Calendar see, add, change and delete only theirs.
 */
class Calendar extends Component
{
    /** The month shown: "2026-11". */
    #[Url(as: 'month')]
    public string $month = '';

    /** The day picked on the month: "2026-11-12". */
    #[Url(as: 'day')]
    public string $day = '';

    /** '' (everything) | meeting | task | event */
    #[Url(as: 'type')]
    public string $typeFilter = '';

    // ─── Add / edit slide-in ────────────────────────────────────────────────
    public bool   $showPanel = false;
    public ?int   $editingId = null;
    public string $fType     = 'meeting';
    public string $fTitle    = '';
    public string $fDate     = '';
    public string $fStart    = '';
    public string $fEnd      = '';
    public string $fWith     = '';
    public string $fLocation = '';
    public string $fNotes    = '';

    // ─── Delete confirmation ───────────────────────────────────────────────
    public ?int $deleteId = null;

    public function mount(): void
    {
        $today = Carbon::today();

        $this->day   = $this->validDate($this->day) ?? $today->toDateString();
        $this->month = $this->validMonth($this->month) ?? Carbon::parse($this->day)->format('Y-m');

        if (!array_key_exists($this->typeFilter, SuperAdminCalendarEvent::TYPES)) {
            $this->typeFilter = '';
        }
    }

    // ─── Moving around ─────────────────────────────────────────────────────
    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonthNoOverflow()->format('Y-m');
    }

    public function goToday(): void
    {
        $today       = Carbon::today();
        $this->day   = $today->toDateString();
        $this->month = $today->format('Y-m');
    }

    public function selectDay(string $date): void
    {
        $date = $this->validDate($date);
        if (!$date) {
            return;
        }

        $this->day   = $date;
        $this->month = Carbon::parse($date)->format('Y-m');
    }

    public function setType(string $type): void
    {
        $this->typeFilter = array_key_exists($type, SuperAdminCalendarEvent::TYPES) ? $type : '';
    }

    // ─── Add / edit ────────────────────────────────────────────────────────
    public function openCreate(?string $date = null, string $type = 'meeting'): void
    {
        $this->resetForm();
        $this->fType     = array_key_exists($type, SuperAdminCalendarEvent::TYPES) ? $type : 'meeting';
        $this->fDate     = $this->validDate((string) $date) ?? $this->day;
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $event = $this->mine()->whereKey($id)->first();
        if (!$event) {
            return;
        }

        $this->resetForm();
        $this->editingId = $event->id;
        $this->fType     = $event->type;
        $this->fTitle    = (string) $event->title;
        $this->fDate     = $event->date->toDateString();
        $this->fStart    = (string) SuperAdminCalendarEvent::hm($event->start_time);
        $this->fEnd      = (string) SuperAdminCalendarEvent::hm($event->end_time);
        $this->fWith     = (string) $event->with_whom;
        $this->fLocation = (string) $event->location;
        $this->fNotes    = (string) $event->notes;
        $this->showPanel = true;
    }

    public function chooseType(string $type): void
    {
        if (array_key_exists($type, SuperAdminCalendarEvent::TYPES)) {
            $this->fType = $type;
        }
    }

    public function save(): void
    {
        $this->fTitle    = trim($this->fTitle);
        $this->fWith     = trim($this->fWith);
        $this->fLocation = trim($this->fLocation);
        $this->fNotes    = trim($this->fNotes);

        $rules = [
            'fType'     => 'required|in:' . implode(',', array_keys(SuperAdminCalendarEvent::TYPES)),
            'fTitle'    => 'required|string|max:191',
            'fDate'     => 'required|date_format:Y-m-d',
            'fStart'    => 'nullable|date_format:H:i',
            'fEnd'      => 'nullable|date_format:H:i',
            'fWith'     => 'nullable|string|max:191',
            'fLocation' => 'nullable|string|max:255',
            'fNotes'    => 'nullable|string|max:2000',
        ];
        if ($this->fStart !== '' && $this->fEnd !== '') {
            $rules['fEnd'] .= '|after:fStart';
        }

        $this->validate($rules, [
            'fTitle.required' => 'Give it a title.',
            'fDate.required'  => 'Pick a date.',
            'fEnd.after'      => 'The end time must be after the start time.',
        ], [
            'fType' => 'type', 'fTitle' => 'title', 'fDate' => 'date', 'fStart' => 'start time',
            'fEnd' => 'end time', 'fWith' => 'with whom', 'fLocation' => 'place or link', 'fNotes' => 'notes',
        ]);

        $data = [
            'type'       => $this->fType,
            'title'      => $this->fTitle,
            'date'       => $this->fDate,
            'start_time' => $this->fStart !== '' ? $this->fStart . ':00' : null,
            'end_time'   => $this->fEnd !== '' ? $this->fEnd . ':00' : null,
            'with_whom'  => $this->fType === 'meeting' && $this->fWith !== '' ? $this->fWith : null,
            'location'   => $this->fLocation !== '' ? $this->fLocation : null,
            'notes'      => $this->fNotes !== '' ? $this->fNotes : null,
        ];

        if ($this->editingId) {
            $event = $this->mine()->whereKey($this->editingId)->first();
            if (!$event) {
                $this->closePanel();
                return;
            }
            if ($this->fType !== 'task') {
                $data['is_done'] = false;
                $data['done_at'] = null;
            }
            $event->update($data);
            $message = 'Updated.';
        } else {
            SuperAdminCalendarEvent::create($data + ['user_id' => Auth::id()]);
            $message = SuperAdminCalendarEvent::TYPES[$this->fType] . ' added.';
        }

        // Show the day it is on.
        $this->day   = $this->fDate;
        $this->month = Carbon::parse($this->fDate)->format('Y-m');

        $this->closePanel();
        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    /** A task ticked off — or back to open. */
    public function toggleDone(int $id): void
    {
        $event = $this->mine()->whereKey($id)->where('type', 'task')->first();
        if (!$event) {
            return;
        }

        $done = !$event->is_done;
        $event->update(['is_done' => $done, 'done_at' => $done ? now() : null]);
    }

    // ─── Delete ────────────────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $this->deleteId = $this->mine()->whereKey($id)->exists() ? $id : null;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $this->mine()->whereKey($this->deleteId)->delete();
            if ($this->editingId === $this->deleteId) {
                $this->closePanel();
            }
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Deleted.']);
        }
        $this->deleteId = null;
    }

    // ─── Rendering ─────────────────────────────────────────────────────────
    public function render()
    {
        $today      = Carbon::today();
        $monthStart = $this->monthStart();
        $gridStart  = $monthStart->clone()->startOfWeek(Carbon::SUNDAY);
        $gridEnd    = $monthStart->clone()->endOfMonth()->endOfWeek(Carbon::SATURDAY)->startOfDay();

        // The month's grid, with what is on each day (the type filter applies).
        $onGrid = $this->filtered($this->mine())
            ->whereDate('date', '>=', $gridStart->toDateString())
            ->whereDate('date', '<=', $gridEnd->toDateString())
            ->get()
            ->sortBy(fn($e) => $this->sortKey($e))
            ->groupBy(fn($e) => $e->date->toDateString());

        $weeks = [];
        for ($d = $gridStart->clone(), $i = 0; $d->lte($gridEnd); $d->addDay(), $i++) {
            $key = $d->toDateString();
            $weeks[intdiv($i, 7)][] = [
                'date'     => $key,
                'num'      => $d->day,
                'inMonth'  => $d->month === $monthStart->month,
                'isToday'  => $d->isSameDay($today),
                'isPicked' => $key === $this->day,
                'items'    => $onGrid->get($key, collect())->values(),
            ];
        }

        $todayItems  = $this->dayItems($today->toDateString());
        $pickedItems = $this->day !== $today->toDateString() ? $this->dayItems($this->day) : null;

        // Tasks left open on earlier days.
        $overdue = $this->mine()
            ->where('type', 'task')
            ->where('is_done', false)
            ->whereDate('date', '<', $today->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(20)
            ->get();

        $todayAll = $this->mine()->whereDate('date', $today->toDateString())->get();
        $stats = [
            'meetings'  => $todayAll->where('type', 'meeting')->count(),
            'tasks'     => $todayAll->where('type', 'task')->count(),
            'tasksDone' => $todayAll->where('type', 'task')->where('is_done', true)->count(),
            'events'    => $todayAll->where('type', 'event')->count(),
        ];

        return view('livewire.super-admin.calendar', [
            'today'       => $today,
            'monthStart'  => $monthStart,
            'weeks'       => $weeks,
            'todayItems'  => $todayItems,
            'pickedDate'  => Carbon::parse($this->day),
            'pickedItems' => $pickedItems,
            'overdue'     => $overdue,
            'stats'       => $stats,
            'types'       => SuperAdminCalendarEvent::TYPES,
            'deleting'    => $this->deleteId ? $this->mine()->whereKey($this->deleteId)->first() : null,
        ]);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────
    /** This user's own calendar — the only rows this screen ever touches. */
    private function mine()
    {
        return SuperAdminCalendarEvent::query()->ownedBy(Auth::id());
    }

    private function filtered($query)
    {
        return $this->typeFilter !== '' ? $query->where('type', $this->typeFilter) : $query;
    }

    private function dayItems(string $date)
    {
        return $this->filtered($this->mine())
            ->whereDate('date', $date)
            ->get()
            ->sortBy(fn($e) => $this->sortKey($e))
            ->values();
    }

    /** Timed items by their time first, then the ones without a time; done tasks last. */
    private function sortKey(SuperAdminCalendarEvent $e): string
    {
        return ($e->is_done ? '1' : '0')
            . ($e->start_time ? '0' . $e->start_time : ($e->end_time ? '0' . $e->end_time : '1'))
            . str_pad((string) $e->id, 10, '0', STR_PAD_LEFT);
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', ($this->validMonth($this->month) ?? Carbon::today()->format('Y-m')) . '-01')->startOfDay();
    }

    private function validDate(string $value): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) && $y >= 2000 && $y <= 2100 ? $value : null;
    }

    private function validMonth(string $value): ?string
    {
        return $this->validDate($value . '-01') ? $value : null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->fType     = 'meeting';
        $this->fTitle    = '';
        $this->fDate     = '';
        $this->fStart    = '';
        $this->fEnd      = '';
        $this->fWith     = '';
        $this->fLocation = '';
        $this->fNotes    = '';
        $this->resetValidation();
    }
}
