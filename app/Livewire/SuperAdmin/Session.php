<?php

namespace App\Livewire\SuperAdmin;

use App\Models\AcademicSession;
use App\Models\Organization;
use App\Support\SessionChangeOtp;
use App\Support\SessionSchoolData;
use App\Support\StudentExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Session: the academic sessions (school years such as
 * 2026–27) — add one with its first and last day, edit it, delete it, and
 * mark which is the current session. The top bar of every panel shows the
 * current session's name.
 *
 * Making a session current takes two codes on one screen: one sent to the
 * Super Admin's registered email, another on WhatsApp (SessionChangeOtp).
 *
 * Each session's › opens its schools — every school on SuperLMS by the
 * session's last day, those registered during it marked New — in the Schools
 * list's look, and each school's › opens all its data for that session
 * (SessionSchoolData): its students, teachers and staff on the roll by the
 * session's end, its classes, the fee that came in and the attendance marked
 * in it, each list with a search and an Excel export.
 *
 * Sessions never overlap, and the current session can't be deleted (another
 * is made current first).
 */
class Session extends Component
{
    use WireUiActions, WithPagination;

    private const EXPIRED = 'This request has expired. Start again.';

    public bool $showPanel = false;

    #[Locked]
    public ?int $editId = null;

    public string $name       = '';
    public string $startsOn   = '';
    public string $endsOn     = '';
    public bool   $makeCurrent = false;

    /** The name was typed by hand: the dates no longer rename it. */
    public bool $nameTouched = false;

    #[Locked]
    public ?int $deleteId = null;

    // ─── Make current: the two codes ─────────────────────────────────────────

    /** The session being made current while the codes screen is open. */
    #[Locked]
    public ?int $switchId = null;

    #[Locked]
    public ?string $switchToken = null;

    /** Where the codes went, masked: ['email' => …, 'whatsapp' => …]. */
    #[Locked]
    public array $switchTo = ['email' => '', 'whatsapp' => ''];

    /** Unix time each code can be sent again at. */
    #[Locked]
    public array $resendAt = ['email' => 0, 'whatsapp' => 0];

    public string $emailCode    = '';
    public string $whatsappCode = '';

    // ─── A session's schools ─────────────────────────────────────────────────

    /** The session whose schools are open (null: the sessions list). */
    #[Url(as: 'schools', except: null)]
    public ?int $schoolsOf = null;

    public string $schoolSearch = '';

    /** '' every school by the session's end | 'new' registered during it | 'earlier' before it. */
    public string $schoolJoined = '';

    public string $schoolStatus = '';

    /** name | newest | students */
    public string $schoolSort = 'name';

    // ─── One school's data in that session ───────────────────────────────────

    /** The school whose data in the open session is shown (null: the session's schools). */
    #[Url(as: 'school', except: null)]
    public ?int $schoolId = null;

    #[Url(as: 'tab', except: 'students')]
    public string $dataTab = 'students';

    public string $dataSearch = '';

    public function mount(): void
    {
        if ($this->schoolsOf && !AcademicSession::whereKey($this->schoolsOf)->exists()) {
            $this->schoolsOf = null;
        }
        if ($this->schoolId && !$this->schoolInOpenSession($this->schoolId)) {
            $this->schoolId = null;
        }
        if (!array_key_exists($this->dataTab, SessionSchoolData::TABS)) {
            $this->dataTab = 'students';
        }

        // The top bar's switcher sends here with ?switch={id}: ask for the codes.
        if ($switch = (int) request()->query('switch', 0)) {
            $this->askMakeCurrent($switch);
            // A reload must not send new codes.
            $this->js('history.replaceState(history.state, "", ' . json_encode(route('super-admin.sessions')) . ')');
        }
    }

    // ─── Add / edit ──────────────────────────────────────────────────────────

    /** A new session: it follows the latest one, a year long. */
    public function openCreate(): void
    {
        $this->resetForm();

        $latest = AcademicSession::orderByDesc('ends_on')->first();
        $start  = $latest
            ? $latest->ends_on->copy()->addDay()
            : Carbon::create(now()->month >= 4 ? now()->year : now()->year - 1, 4, 1);

        $this->startsOn    = $start->toDateString();
        $this->endsOn      = $start->copy()->addYear()->subDay()->toDateString();
        $this->name        = AcademicSession::nameFor($start, Carbon::parse($this->endsOn));
        // With no current session the new one is offered as current — still behind the codes.
        $this->makeCurrent = !AcademicSession::where('is_current', true)->exists();
        $this->showPanel   = true;
    }

    public function edit(int $id): void
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        $this->resetForm();
        $this->editId      = $session->id;
        $this->name        = $session->name;
        $this->startsOn    = $session->starts_on->toDateString();
        $this->endsOn      = $session->ends_on->toDateString();
        $this->makeCurrent = $session->is_current;
        $this->nameTouched = $session->name !== AcademicSession::nameFor($session->starts_on, $session->ends_on);
        $this->showPanel   = true;
    }

    /** A new first day: the last day follows a year on, if it was before it; the name follows the dates. */
    public function updatedStartsOn(): void
    {
        $start = $this->date($this->startsOn);
        $end   = $this->date($this->endsOn);

        if ($start && (!$end || $end->lte($start))) {
            $this->endsOn = $start->copy()->addYear()->subDay()->toDateString();
        }

        $this->rename();
    }

    public function updatedEndsOn(): void
    {
        $this->rename();
    }

    public function updatedName(): void
    {
        $this->nameTouched = trim($this->name) !== '';
    }

    public function save(): void
    {
        $this->name = trim($this->name);

        // The dates first: overlapping dates name themselves like the session
        // they overlap, and "overlaps 2026–27" says more than "name taken".
        $this->validate([
            'startsOn' => ['required', 'date'],
            'endsOn'   => ['required', 'date', 'after:startsOn'],
        ], [
            'startsOn.required' => 'Pick the first day of the session.',
            'endsOn.required'   => 'Pick the last day of the session.',
            'endsOn.after'      => 'The last day must come after the first day.',
        ]);

        $start = Carbon::parse($this->startsOn)->startOfDay();
        $end   = Carbon::parse($this->endsOn)->startOfDay();

        if ($start->copy()->addMonths(24)->lte($end)) {
            $this->addError('endsOn', 'A session can be at most 2 years long.');
            return;
        }

        $overlap = AcademicSession::query()
            ->when($this->editId, fn ($q) => $q->whereKeyNot($this->editId))
            ->whereDate('starts_on', '<=', $end)
            ->whereDate('ends_on', '>=', $start)
            ->orderBy('starts_on')
            ->first();

        if ($overlap) {
            $this->addError('startsOn', "These dates overlap {$overlap->name} ({$overlap->durationLabel()}).");
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_sessions', 'name')->ignore($this->editId)],
        ], [
            'name.required' => 'Give the session a name, e.g. 2026–27.',
            'name.max'      => 'Keep the name to 20 characters.',
            'name.unique'   => 'A session with this name is already there.',
        ]);

        $session = $this->editId ? AcademicSession::find($this->editId) : new AcademicSession();

        if (!$session) {
            $this->closePanel();
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        $session->fill(['name' => $this->name, 'starts_on' => $start, 'ends_on' => $end])->save();

        $editing      = (bool) $this->editId;
        $wantsCurrent = $this->makeCurrent && !$session->is_current;
        $this->closePanel();

        if ($wantsCurrent) {
            // Saved; becoming current still takes the two codes.
            $this->notification()->success($editing ? 'Session updated' : 'Session added', "Enter the two codes to make {$session->name} current.");
            $this->askMakeCurrent($session->id);
            return;
        }

        $this->notification()->success(
            $editing ? 'Session updated' : 'Session added',
            $session->is_current ? "{$session->name} is the current session." : "{$session->name} is saved."
        );
    }

    // ─── Make current — behind an email code and a WhatsApp code ────────────

    /** Send the two codes and open the screen to enter them. */
    public function askMakeCurrent(int $id): void
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        if ($session->is_current) {
            $this->notification()->info('Already current', "{$session->name} is the current session.");
            return;
        }

        $user = Auth::user();

        try {
            [$token, $delivery] = SessionChangeOtp::start($user, $session);
        } catch (\RuntimeException $e) {
            $this->notification()->error('The codes can’t be sent', $e->getMessage());
            return;
        }

        SessionChangeOtp::forget($this->switchToken);
        $this->resetValidation();
        $this->reset(['emailCode', 'whatsappCode']);

        $where             = SessionChangeOtp::destinations($user);
        $this->switchId    = $session->id;
        $this->switchToken = $token;
        $this->switchTo    = ['email' => $where['email'], 'whatsapp' => $where['whatsapp']];
        $this->resendAt    = SessionChangeOtp::resendAt($user, $token);

        foreach ($delivery as $channel => $result) {
            if (!$result['ok']) {
                $this->addError($channel . 'Code', 'The code could not be sent — ' . $result['error'] . '. Try Resend in a minute.');
            }
        }
    }

    public function resendCode(string $channel): void
    {
        if (!$this->switchId || !in_array($channel, SessionChangeOtp::CHANNELS, true)) {
            return;
        }

        $user   = Auth::user();
        $result = SessionChangeOtp::resend($user, $this->switchToken, $channel);
        $field  = $channel . 'Code';

        $this->resetValidation([$field, 'request']);
        $this->resendAt = SessionChangeOtp::resendAt($user, $this->switchToken);

        if ($result['wait']) {
            return; // the page counts down the time left
        }

        if (!$result['ok']) {
            $result['error'] === self::EXPIRED
                ? $this->addError('request', 'These codes have expired. Close this and click Make current again.')
                : $this->addError($field, 'The code could not be sent — ' . $result['error'] . '.');
            return;
        }

        $this->{$field} = '';
        $this->notification()->success('Code sent', $channel === 'email' ? 'A new code is on its way to the email.' : 'A new code is on its way on WhatsApp.');
    }

    public function verifyMakeCurrent(): void
    {
        $this->resetValidation(['emailCode', 'whatsappCode', 'request']);

        $codes = [
            'email'    => preg_replace('/\D+/', '', $this->emailCode),
            'whatsapp' => preg_replace('/\D+/', '', $this->whatsappCode),
        ];

        $short = false;
        foreach ($codes as $channel => $code) {
            if (strlen($code) !== 6) {
                $this->addError($channel . 'Code', $code === '' ? 'Enter the 6-digit code.' : 'The code has 6 digits.');
                $short = true;
            }
        }
        if ($short) {
            return;
        }

        $errors = SessionChangeOtp::verify(Auth::user(), $this->switchToken, (int) $this->switchId, $codes['email'], $codes['whatsapp']);

        if ($errors) {
            foreach (SessionChangeOtp::CHANNELS as $channel) {
                if (isset($errors[$channel])) {
                    $this->addError($channel . 'Code', $errors[$channel]);
                }
            }
            if (isset($errors['request'])) {
                $this->switchToken = null;
                $this->addError('request', $errors['request']);
            } elseif (isset($errors['tries'])) {
                $this->addError('request', $errors['tries']);
            }
            return;
        }

        $session = AcademicSession::find($this->switchId);
        $this->closeSwitch();

        if (!$session) {
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        $session->makeCurrent();

        $this->dispatch('academic-session-changed', id: $session->id);
        $this->notification()->success('Current session changed', "{$session->name} is now the current session.");
    }

    public function closeSwitch(): void
    {
        SessionChangeOtp::forget($this->switchToken);
        $this->reset(['switchId', 'switchToken', 'switchTo', 'resendAt', 'emailCode', 'whatsappCode']);
        $this->resetValidation(['emailCode', 'whatsappCode', 'request']);
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $session = AcademicSession::find($id);

        if ($session?->is_current) {
            $this->notification()->warning('This is the current session', 'Make another session current first, then delete this one.');
            return;
        }

        $this->deleteId = $session?->id;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function delete(): void
    {
        $session = $this->deleteId ? AcademicSession::find($this->deleteId) : null;
        $this->deleteId = null;

        if (!$session) {
            return;
        }

        if ($session->is_current) {
            $this->notification()->warning('This is the current session', 'Make another session current first, then delete this one.');
            return;
        }

        $session->delete();
        if ($this->schoolsOf === $session->id) {
            $this->backToSessions();
        }
        $this->notification()->success('Session deleted', "{$session->name} is removed.");
    }

    // ─── A session's schools ─────────────────────────────────────────────────

    public function openSchools(int $id): void
    {
        if (!AcademicSession::whereKey($id)->exists()) {
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        $this->schoolsOf = $id;
        $this->reset(['schoolSearch', 'schoolJoined', 'schoolStatus', 'schoolSort', 'schoolId', 'dataTab', 'dataSearch']);
        $this->resetPage();
    }

    public function backToSessions(): void
    {
        $this->schoolsOf = null;
        $this->reset(['schoolSearch', 'schoolJoined', 'schoolStatus', 'schoolSort', 'schoolId', 'dataTab', 'dataSearch']);
        $this->resetPage();
    }

    /** A school's › on the session's schools: its data in that session. */
    public function openSchoolData(int $orgId): void
    {
        if (!$this->schoolInOpenSession($orgId)) {
            $this->notification()->error('Not found', 'This school was not on SuperLMS in this session.');
            return;
        }

        $this->schoolId = $orgId;
        $this->reset(['dataTab', 'dataSearch']);
        $this->resetPage(SessionSchoolData::PAGE);
    }

    /** Back to the session's schools, its filters and page as they were. */
    public function backToSchools(): void
    {
        $this->reset(['schoolId', 'dataTab', 'dataSearch']);
        $this->resetPage(SessionSchoolData::PAGE);
    }

    public function setDataTab(string $tab): void
    {
        if (!array_key_exists($tab, SessionSchoolData::TABS)) {
            return;
        }

        $this->dataTab    = $tab;
        $this->dataSearch = '';
        $this->resetPage(SessionSchoolData::PAGE);
    }

    public function updatedDataTab(): void
    {
        $this->setDataTab(array_key_exists($this->dataTab, SessionSchoolData::TABS) ? $this->dataTab : 'students');
    }

    public function updatedDataSearch(): void
    {
        $this->resetPage(SessionSchoolData::PAGE);
    }

    /** The open tab as Excel — the school's whole list for the session, not one page. */
    public function exportData()
    {
        $data = $this->schoolData();
        $org  = $data ? Organization::find($this->schoolId) : null;

        if (!$data || !$org) {
            return null;
        }

        try {
            [$headings, $rows] = $data->sheet($this->dataTab);
            $bytes = StudentExport::xlsx($headings, $rows);
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Export failed', 'The Excel file could not be made. Please try again.');
            return null;
        }

        return response()->streamDownload(fn () => print($bytes), $data->filename($org->name, $this->dataTab), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Whether a school was on SuperLMS by the open session's end (and is one this user may see). */
    private function schoolInOpenSession(int $orgId): bool
    {
        $session = $this->schoolsOf ? AcademicSession::find($this->schoolsOf) : null;

        return $session && $this->schoolsBy($session)->whereKey($orgId)->exists();
    }

    /** The open school's data in the open session, when both are open. */
    private function schoolData(): ?SessionSchoolData
    {
        $session = $this->schoolsOf && $this->schoolId ? AcademicSession::find($this->schoolsOf) : null;

        return $session && $this->schoolsBy($session)->whereKey($this->schoolId)->exists()
            ? new SessionSchoolData($this->schoolId, $session)
            : null;
    }

    public function clearSchoolFilters(): void
    {
        $this->reset(['schoolSearch', 'schoolJoined', 'schoolStatus', 'schoolSort']);
        $this->resetPage();
    }

    public function updatedSchoolSearch(): void { $this->resetPage(); }
    public function updatedSchoolJoined(): void { $this->resetPage(); }
    public function updatedSchoolStatus(): void { $this->resetPage(); }
    public function updatedSchoolSort(): void   { $this->resetPage(); }

    /** Another screen made another session current: show it. */
    #[On('academic-session-changed')]
    public function sessionSwitched(): void
    {
        // Re-rendering is all it takes.
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'name', 'startsOn', 'endsOn', 'makeCurrent', 'nameTouched']);
        $this->resetValidation();
    }

    /** The name follows the dates until it is typed by hand. */
    private function rename(): void
    {
        $start = $this->date($this->startsOn);
        $end   = $this->date($this->endsOn);

        if (!$this->nameTouched && $start && $end) {
            $this->name = AcademicSession::nameFor($start, $end);
        }
    }

    private function date(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::parse($value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** A sub super-admin limited to one school sees that school only. */
    private function lockedSchool(): ?int
    {
        $user = Auth::user();

        return $user && $user->isSubSuperAdmin() ? $user->allowedOrganizationId() : null;
    }

    /**
     * Schools on SuperLMS by a session's last day (registered on or before
     * it); a school with no date counts as always there.
     */
    private function schoolsBy(AcademicSession $session)
    {
        $end = $session->ends_on->copy()->endOfDay();

        return Organization::query()
            ->when($this->lockedSchool(), fn ($q, $id) => $q->whereKey($id))
            ->where(fn ($q) => $q->whereNull('created_at')->orWhere('created_at', '<=', $end));
    }

    /** Each session's schools: on SuperLMS by its end, and registered during it. */
    private function schoolCounts($sessions): array
    {
        $dates = Organization::query()
            ->when($this->lockedSchool(), fn ($q, $id) => $q->whereKey($id))
            ->pluck('created_at')
            ->map(fn ($d) => $d ? Carbon::parse($d) : null);

        $out = [];
        foreach ($sessions as $session) {
            $start = $session->starts_on->copy()->startOfDay();
            $end   = $session->ends_on->copy()->endOfDay();

            $out[$session->id] = [
                'total' => $dates->filter(fn ($d) => !$d || $d->lte($end))->count(),
                'new'   => $dates->filter(fn ($d) => $d && $d->between($start, $end))->count(),
            ];
        }

        return $out;
    }

    public function render()
    {
        $sessions = AcademicSession::orderByDesc('starts_on')->get();
        $open     = $this->schoolsOf ? $sessions->firstWhere('id', $this->schoolsOf) : null;

        $data = [
            'sessions'     => $sessions,
            'counts'       => $this->schoolCounts($sessions),
            'switching'    => $this->switchId ? $sessions->firstWhere('id', $this->switchId) : null,
            'open'         => $open,
            'schools'      => null,
            'schoolTotals' => null,
            'start'        => null,
            'end'          => null,
            'school'       => null,
        ];

        // ── One school's data in the open session ──
        $schoolData = $open && $this->schoolId ? $this->schoolData() : null;
        if ($this->schoolId && !$schoolData) {
            $this->schoolId = null; // gone, or not one of this session's schools
        }

        if ($schoolData) {
            if (!array_key_exists($this->dataTab, SessionSchoolData::TABS)) {
                $this->dataTab = 'students';
            }

            $data['school'] = [
                'org'        => Organization::find($this->schoolId),
                'data'       => $schoolData,
                'summary'    => $schoolData->summary(),
                'rows'       => in_array($this->dataTab, ['classes', 'attendance'], true) ? null : $schoolData->rows($this->dataTab, $this->dataSearch),
                'classes'    => $this->dataTab === 'classes' ? $schoolData->classes($this->dataSearch) : [],
                'attendance' => $this->dataTab === 'attendance' ? $schoolData->attendanceByMonth() : [],
            ];

            return view('livewire.super-admin.session', $data);
        }

        if ($open) {
            $start = $open->starts_on->copy()->startOfDay();
            $end   = $open->ends_on->copy()->endOfDay();
            // Students and teachers on the school's roll by the session's end.
            $byEnd = fn ($q) => $q->where(fn ($w) => $w->whereNull('created_at')->orWhere('created_at', '<=', $end));
            $term  = trim($this->schoolSearch);

            $data['schools'] = $this->schoolsBy($open)
                ->withCount(['students as total_students' => $byEnd, 'teachers as total_teachers' => $byEnd])
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('school_code', 'like', "%{$term}%")))
                ->when($this->schoolJoined === 'new', fn ($q) => $q->whereBetween('created_at', [$start, $end]))
                ->when($this->schoolJoined === 'earlier', fn ($q) => $q->where(fn ($w) => $w->whereNull('created_at')->orWhere('created_at', '<', $start)))
                ->when($this->schoolStatus !== '', fn ($q) => $q->where('status', $this->schoolStatus === 'active'))
                ->tap(fn ($q) => match ($this->schoolSort) {
                    'newest'   => $q->orderByDesc('created_at'),
                    'students' => $q->orderByDesc('total_students'),
                    default    => $q->orderBy('name'),
                })
                ->orderBy('id')
                ->paginate(20);

            $all = $this->schoolsBy($open)
                ->withCount(['students as total_students' => $byEnd, 'teachers as total_teachers' => $byEnd])
                ->get(['id', 'status', 'created_at']);

            $data['schoolTotals'] = [
                'schools'  => $all->count(),
                'new'      => $all->filter(fn ($o) => $o->created_at && $o->created_at->between($start, $end))->count(),
                'active'   => $all->filter(fn ($o) => (bool) $o->status)->count(),
                'students' => (int) $all->sum('total_students'),
                'teachers' => (int) $all->sum('total_teachers'),
            ];
            $data['start'] = $start;
            $data['end']   = $end;
        }

        return view('livewire.super-admin.session', $data);
    }
}
