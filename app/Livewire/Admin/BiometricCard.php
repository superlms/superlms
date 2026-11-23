<?php

namespace App\Livewire\Admin;

use App\Models\Biometric\BiometricCard as CardRow;
use App\Support\Biometric;
use App\Support\BiometricFk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * The 125 kHz card of the student or teacher open in a View panel.
 *
 * A school with a SecurEye-style machine assigns it on the machine: Assign
 * card shows this person's machine number and waits, the card is tapped on the
 * machine's Register screen under that number, and the machine tells us
 * (App\Support\BiometricFk::keepEnrolment). Such a machine reports nothing for
 * a card it does not know, so the tap has to be on that screen.
 *
 * Otherwise it is tapped on a USB card reader (which types the card's number
 * and Enter into the box) and goes to the school's machines with them
 * (App\Support\Biometric::assignCard). Shown only while the school has
 * biometric attendance on.
 */
class BiometricCard extends Component
{
    public string $holderType = 'student';   // student | teacher
    public int $holderId = 0;

    public bool $listening = false;
    public string $cardInput = '';

    /** The person's card when the wait on the machine began. */
    public string $cardBefore = '';

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public function mount(string $holderType, int $holderId): void
    {
        $this->holderType = in_array($holderType, Biometric::HOLDERS, true) ? $holderType : 'student';
        $this->holderId   = $holderId;
    }

    public function listen(): void
    {
        $this->cardInput = '';
        $this->resetErrorBag();
        Cache::forget(BiometricFk::cardErrorKey($this->holderType, $this->holderId));
        $this->cardBefore = $this->cardStamp();
        $this->listening = true;
    }

    public function cancel(): void
    {
        $this->listening = false;
        $this->cardInput = '';
        $this->resetErrorBag();
    }

    /** While waiting: has the machine told us about this person's card? */
    public function checkMachine(): void
    {
        if (!$this->listening || !Biometric::enabled($this->orgId())) {
            return;
        }

        $error = Cache::pull(BiometricFk::cardErrorKey($this->holderType, $this->holderId));
        if ($error) {
            $this->addError('cardInput', $error);
            return;
        }

        if ($this->cardStamp() !== $this->cardBefore) {
            $this->listening = false;
            $this->resetErrorBag();
            $card = Biometric::cardOf($this->holderType, $this->holderId);
            $this->dispatch('notify', ['type' => 'success', 'message' => "Card {$card} assigned from the machine."]);
        }
    }

    private function cardStamp(): string
    {
        $row = CardRow::where('organization_id', $this->orgId())
            ->where('holder_type', $this->holderType)->where('holder_id', $this->holderId)->first();

        return $row ? $row->card_number . '|' . $row->updated_at : '';
    }

    /** The reader's Enter: the card is this person's. */
    public function save(): void
    {
        if (!Biometric::enabled($this->orgId())) {
            return;
        }

        try {
            $card = Biometric::assignCard($this->orgId(), $this->holderType, $this->holderId, $this->cardInput, Auth::id());
        } catch (\RuntimeException $e) {
            $this->cardInput = '';
            $this->addError('cardInput', $e->getMessage());
            return;
        }

        $this->listening = false;
        $this->cardInput = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => "Card {$card} assigned — the machines get it in a few seconds."]);
    }

    public function remove(): void
    {
        if (!Biometric::enabled($this->orgId())) {
            return;
        }

        Biometric::removeCard($this->orgId(), $this->holderType, $this->holderId);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Card removed.']);
    }

    public function render()
    {
        $machine = Biometric::hasFkMachine($this->orgId());

        return view('livewire.admin.biometric-card', [
            'on'      => Biometric::enabled($this->orgId()),
            'card'    => Biometric::cardOf($this->holderType, $this->holderId),
            'machine' => $machine,
            'number'  => $machine
                ? BiometricFk::panelNumber($this->orgId(), $this->holderType, $this->holderId)
                : Biometric::pin($this->holderType, $this->holderId),
        ]);
    }
}
