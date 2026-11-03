<?php

namespace App\Livewire\SuperAdmin;

use App\Models\AcademicSession;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Session: the academic sessions (school years such as
 * 2026–27) — add one with its first and last day, edit it, delete it, and
 * mark which is the current session. The top bar of every panel shows the
 * current session's name.
 *
 * Sessions never overlap, and the current session can't be deleted (another
 * is made current first). The first session added becomes current by itself.
 */
class Session extends Component
{
    use WireUiActions;

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
        $this->makeCurrent = !$latest;
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

        // The first session — or one marked so — is the current one.
        if ($this->makeCurrent || !AcademicSession::where('is_current', true)->exists()) {
            $session->makeCurrent();
        }

        $editing = (bool) $this->editId;
        $this->closePanel();
        $this->notification()->success(
            $editing ? 'Session updated' : 'Session added',
            $session->is_current ? "{$session->name} is the current session." : "{$session->name} is saved."
        );
    }

    public function setCurrent(int $id): void
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            $this->notification()->error('Not found', 'This session is no longer there.');
            return;
        }

        $session->makeCurrent();
        $this->notification()->success('Current session changed', "{$session->name} is now the current session.");
    }

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
        $this->notification()->success('Session deleted', "{$session->name} is removed.");
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

    public function render()
    {
        return view('livewire.super-admin.session', [
            'sessions' => AcademicSession::orderByDesc('starts_on')->get(),
        ]);
    }
}
