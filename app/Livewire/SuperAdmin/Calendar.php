<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use App\Models\User;
use App\Models\WebsiteDemo;
use App\Services\DemoMessages;
use App\Services\MeetLink;
use App\Support\DemoBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super Admin → Calendar, in two tabs.
 *
 * Calendar: the signed-in person's own meetings (when, with whom, where),
 * tasks (ticked off when done) and events. The month fits the window, with
 * its previous / next / Today on the card itself; Today's list — and the day
 * picked on the month — on the right, with the tasks left open from earlier
 * days. Every row is the signed-in user's own (user_id): the super admin and
 * each sub-super-admin given Calendar see, add, change and delete only theirs.
 *
 * Queries: the website's demo requests (WebsiteDemo) with when they came and
 * their status — Pending until a time is picked on the request's own booking
 * link, then Booked (with its Meet link, and on the calendar of the person
 * set in Booking settings), Completed or Cancelled. Shown to whoever may open
 * Website Enquiries (the main super admin, or a sub-super-admin given it).
 */
class Calendar extends Component
{
    use WithPagination;

    public const TABS = ['calendar', 'queries'];

    #[Url(as: 'tab', except: 'calendar')]
    public string $tab = 'calendar';

    /** The month shown: "2026-11". */
    #[Url(as: 'month')]
    public string $month = '';

    /** The day picked on the month: "2026-11-12". */
    #[Url(as: 'day')]
    public string $day = '';

    // ─── Calendar filters ──────────────────────────────────────────────────
    /** '' (everything) | meeting | task | event */
    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** '' | open | done */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    // ─── Queries filters ───────────────────────────────────────────────────
    #[Url(as: 'qs', except: '')]
    public string $qSearch = '';

    /** '' | pending | booked | completed | cancelled */
    #[Url(as: 'qstatus', except: '')]
    public string $qStatus = '';

    /** '' (all time) | 7 | 30 | 90 days */
    #[Url(as: 'qdays', except: '')]
    public string $qDays = '';

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

    // ─── Queries: a request's slide-in, its delete ─────────────────────────
    public ?int $viewQueryId   = null;
    public ?int $deleteQueryId = null;

    // ─── Booking settings slide-in ─────────────────────────────────────────
    public bool   $showSettings = false;
    public array  $sDays        = [];
    public string $sStart       = '10:00';
    public string $sEnd         = '18:00';
    public string $sMinutes     = '30';
    public string $sDaysAhead   = '14';
    public string $sNotice      = '3';
    public string $sOwner       = '';

    /** thanks | confirmed: the email shown in a preview over the settings. */
    public ?string $previewEmail = null;

    public function mount(): void
    {
        $today = Carbon::today();

        $this->day   = $this->validDate($this->day) ?? $today->toDateString();
        $this->month = $this->validMonth($this->month) ?? Carbon::parse($this->day)->format('Y-m');

        if (!array_key_exists($this->typeFilter, SuperAdminCalendarEvent::TYPES)) {
            $this->typeFilter = '';
        }
        if (!in_array($this->tab, self::TABS, true) || ($this->tab === 'queries' && !$this->canSeeQueries())) {
            $this->tab = 'calendar';
        }
    }

    // ─── Tabs and filters ──────────────────────────────────────────────────
    public function switchTab(string $tab): void
    {
        if (!in_array($tab, self::TABS, true) || ($tab === 'queries' && !$this->canSeeQueries())) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
        $this->closePanel();
        $this->closeQuery();
        $this->showSettings = false;
    }

    public function updatedQSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQStatus(): void
    {
        $this->resetPage();
    }

    public function updatedQDays(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        if ($this->tab === 'queries') {
            $this->qSearch = $this->qStatus = $this->qDays = '';
            $this->resetPage();

            return;
        }

        $this->search = $this->statusFilter = $this->typeFilter = '';
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

    public function updatedTypeFilter(): void
    {
        $this->setType($this->typeFilter);
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
        $this->deleteId      = null;
        $this->deleteQueryId = null;
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

    // ─── Queries ───────────────────────────────────────────────────────────
    public function viewQuery(int $id): void
    {
        if (!$this->canSeeQueries() || !WebsiteDemo::whereKey($id)->exists()) {
            return;
        }

        $this->viewQueryId = $id;
    }

    public function closeQuery(): void
    {
        $this->viewQueryId = null;
    }

    /** The request's booking link to the clipboard (made the first time). */
    public function copyBookingLink(int $id): void
    {
        $demo = $this->query($id);
        if (!$demo) {
            return;
        }

        $this->dispatch('copy-text', text: $demo->bookingUrl());
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Booking link copied.']);
    }

    /** The thank-you with the booking link, sent again by email and WhatsApp. */
    public function resendBookingLink(int $id): void
    {
        $demo = $this->query($id);
        if (!$demo) {
            return;
        }
        if (in_array($demo->statusKey(), ['completed', 'cancelled'], true)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Reopen the request first.']);
            return;
        }

        $result = DemoMessages::thanks($demo);
        $sent   = array_keys(array_filter(['email' => $result['email'] === 'ok', 'WhatsApp' => $result['whatsapp'] === 'ok']));

        $this->dispatch('notify', $sent
            ? ['type' => 'success', 'message' => 'Booking link sent by ' . implode(' and ', $sent) . '.']
            : ['type' => 'error', 'message' => 'Not sent: ' . $result['email']]);
    }

    /** complete | cancel | reopen */
    public function setQueryStatus(int $id, string $action): void
    {
        $demo = $this->query($id);
        if (!$demo) {
            return;
        }

        $status = $demo->statusKey();
        if ($action === 'complete' && $status !== 'completed') {
            $demo->update(['status' => 'completed']);
            $message = 'Marked as completed.';
        } elseif ($action === 'cancel' && in_array($status, ['pending', 'booked'], true)) {
            DemoBooking::cancel($demo);
            $message = 'Demo cancelled' . ($status === 'booked' ? ' and taken off the calendar.' : '.');
        } elseif ($action === 'reopen' && in_array($status, ['completed', 'cancelled'], true)) {
            DemoBooking::reopen($demo);
            $message = 'Reopened — the booking link works again.';
        } else {
            return;
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
    }

    public function confirmDeleteQuery(int $id): void
    {
        $this->deleteQueryId = $this->query($id)?->id;
    }

    public function deleteQuery(): void
    {
        $demo = $this->deleteQueryId ? $this->query($this->deleteQueryId) : null;
        if ($demo) {
            if ($demo->statusKey() === 'booked') {
                DemoBooking::cancel($demo);
            }
            $demo->delete();
            if ($this->viewQueryId === $demo->id) {
                $this->closeQuery();
            }
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Query deleted.']);
        }
        $this->deleteQueryId = null;
    }

    // ─── Booking settings ──────────────────────────────────────────────────
    public function openSettings(): void
    {
        if (!$this->canSeeQueries()) {
            return;
        }

        $s = DemoBooking::settings();
        $this->sDays      = array_map('strval', $s['days']);
        $this->sStart     = $s['start'];
        $this->sEnd       = $s['end'];
        $this->sMinutes   = (string) $s['minutes'];
        $this->sDaysAhead = (string) $s['days_ahead'];
        $this->sNotice    = (string) $s['notice'];
        $this->sOwner     = (string) (DemoBooking::ownerId() ?? '');
        $this->resetValidation();
        $this->closeQuery();
        $this->showSettings = true;
    }

    public function saveSettings(): void
    {
        if (!$this->canSeeQueries()) {
            return;
        }

        $this->validate([
            'sDays'      => 'required|array|min:1',
            'sDays.*'    => 'in:0,1,2,3,4,5,6',
            'sStart'     => 'required|date_format:H:i',
            'sEnd'       => 'required|date_format:H:i|after:sStart',
            'sMinutes'   => 'required|in:' . implode(',', DemoBooking::LENGTHS),
            'sDaysAhead' => 'required|integer|min:1|max:60',
            'sNotice'    => 'required|integer|min:0|max:72',
            'sOwner'     => 'nullable|integer',
        ], [
            'sDays.required' => 'Pick at least one day.',
            'sDays.min'      => 'Pick at least one day.',
            'sEnd.after'     => 'The last time must be after the first.',
        ]);

        $owner = $this->sOwner !== '' && $this->ownerChoices()->has((int) $this->sOwner) ? (int) $this->sOwner : null;

        DemoBooking::save([
            'days'       => array_map('intval', $this->sDays),
            'start'      => $this->sStart,
            'end'        => $this->sEnd,
            'minutes'    => (int) $this->sMinutes,
            'days_ahead' => (int) $this->sDaysAhead,
            'notice'     => (int) $this->sNotice,
            'owner_id'   => $owner,
        ]);

        $this->showSettings = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Booking settings saved.']);
    }

    public function closeSettings(): void
    {
        $this->showSettings = false;
        $this->resetValidation();
    }

    public function showEmailPreview(string $which): void
    {
        $this->previewEmail = in_array($which, ['thanks', 'confirmed'], true) && $this->canSeeQueries() ? $which : null;
    }

    public function closePreview(): void
    {
        $this->previewEmail = null;
    }

    public function disconnectGoogle(): void
    {
        if (Auth::user()?->role !== 'super-admin') {
            return;
        }

        MeetLink::disconnect();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Google disconnected — new demos get a Jitsi Meet link.']);
    }

    // ─── Rendering ─────────────────────────────────────────────────────────
    public function render()
    {
        $canSeeQueries = $this->canSeeQueries();
        if ($this->tab === 'queries' && !$canSeeQueries) {
            $this->tab = 'calendar';
        }

        $data = [
            'types'         => SuperAdminCalendarEvent::TYPES,
            'canSeeQueries' => $canSeeQueries,
            'queryCounts'   => $canSeeQueries ? $this->queryCounts() : null,
            'deleting'      => $this->deleteId ? $this->mine()->whereKey($this->deleteId)->first() : null,
            'deletingQuery' => $this->deleteQueryId && $canSeeQueries ? WebsiteDemo::find($this->deleteQueryId) : null,
        ];

        return view('livewire.super-admin.calendar', $data + ($this->tab === 'queries' ? $this->queriesData() : $this->calendarData()));
    }

    private function calendarData(): array
    {
        $today      = Carbon::today();
        $monthStart = $this->monthStart();
        $gridStart  = $monthStart->clone()->startOfWeek(Carbon::SUNDAY);
        $gridEnd    = $monthStart->clone()->endOfMonth()->endOfWeek(Carbon::SATURDAY)->startOfDay();

        // The month's grid, with what is on each day (the filters apply).
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
            'month'     => $this->mine()
                ->whereDate('date', '>=', $monthStart->toDateString())
                ->whereDate('date', '<=', $monthStart->clone()->endOfMonth()->toDateString())
                ->count(),
        ];

        return [
            'today'       => $today,
            'monthStart'  => $monthStart,
            'weeks'       => $weeks,
            'todayItems'  => $todayItems,
            'pickedDate'  => Carbon::parse($this->day),
            'pickedItems' => $pickedItems,
            'overdue'     => $overdue,
            'stats'       => $stats,
            'filtered'    => $this->search !== '' || $this->statusFilter !== '' || $this->typeFilter !== '',
        ];
    }

    private function queriesData(): array
    {
        $term = trim($this->qSearch);

        $queries = WebsiteDemo::query()
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';
                $q->where(fn ($w) => $w->where('full_name', 'like', $like)
                    ->orWhere('school_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('city', 'like', $like));
            })
            ->when($this->qStatus === 'pending', fn ($q) => $q->where(fn ($w) => $w->where('status', 'pending')->orWhereNull('status')))
            ->when(in_array($this->qStatus, ['booked', 'completed', 'cancelled'], true), fn ($q) => $q->where('status', $this->qStatus))
            ->when(in_array($this->qDays, ['7', '30', '90'], true), fn ($q) => $q->where('created_at', '>=', Carbon::now()->subDays((int) $this->qDays)))
            ->latest()
            ->latest('id')
            ->paginate(15);

        $viewing = $this->viewQueryId ? WebsiteDemo::find($this->viewQueryId) : null;
        if ($viewing) {
            $viewing->ensureToken();
        }

        return [
            'queries'        => $queries,
            'viewing'        => $viewing,
            'filtered'       => $term !== '' || $this->qStatus !== '' || $this->qDays !== '',
            'settings'       => DemoBooking::settings(),
            'ownerChoices'   => $this->showSettings ? $this->ownerChoices() : collect(),
            'googleReady'    => MeetLink::googleReady(),
            'googleSetUp'    => MeetLink::googleConfigured(),
            'googleEmail'    => MeetLink::googleEmail(),
            'isMainAdmin'    => Auth::user()?->role === 'super-admin',
            'templates'      => $this->showSettings ? DemoBooking::whatsAppTemplates() : [],
            'previewHtml'    => $this->previewEmail ? DemoMessages::preview($this->previewEmail) : null,
        ];
    }

    /** The Queries tab's figures (and its count on the tab). */
    private function queryCounts(): array
    {
        $by = WebsiteDemo::query()->selectRaw("COALESCE(status, 'pending') as s, COUNT(*) as n")->groupBy('s')->pluck('n', 's');

        return [
            'total'     => (int) $by->sum(),
            'pending'   => (int) ($by['pending'] ?? 0),
            'booked'    => (int) ($by['booked'] ?? 0),
            'completed' => (int) ($by['completed'] ?? 0),
            'upcoming'  => WebsiteDemo::where('status', 'booked')->where('meeting_at', '>=', Carbon::now())->count(),
            'month'     => WebsiteDemo::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
        ];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────
    /** This user's own calendar — the only rows this screen ever touches. */
    private function mine()
    {
        return SuperAdminCalendarEvent::query()->ownedBy(Auth::id());
    }

    /** Website demo requests are seen by whoever may open Website Enquiries. */
    private function canSeeQueries(): bool
    {
        return (bool) Auth::user()?->canAccessSuperAdminRoute('super-admin.enquiries');
    }

    private function query(int $id): ?WebsiteDemo
    {
        return $this->canSeeQueries() ? WebsiteDemo::find($id) : null;
    }

    /** Whose calendar a booked demo may go on: the super admin, and sub-super-admins given Calendar. */
    private function ownerChoices()
    {
        return User::whereIn('role', ['super-admin', 'sub-super-admin'])->orderByRaw("role = 'super-admin' desc")->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'permissions'])
            ->filter(fn (User $u) => $u->canAccessSuperAdminRoute('super-admin.calendar'))
            ->keyBy('id');
    }

    private function filtered($query)
    {
        $term = trim($this->search);

        return $query
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter === 'open', fn ($q) => $q->where('is_done', false))
            ->when($this->statusFilter === 'done', fn ($q) => $q->where('type', 'task')->where('is_done', true))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('with_whom', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('notes', 'like', $like));
            });
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
