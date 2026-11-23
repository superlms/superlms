<?php

namespace App\Livewire\Admin;

use App\Support\Biometric;
use App\Support\BiometricFk;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The card row of the student or teacher open in a View panel.
 *
 * A school with a SecurEye-style machine sees the Card Number once a card is
 * assigned: the number the person goes by on the machine (a student's roll, a
 * teacher's Employee ID), with Remove beside it; otherwise "Not assigned". The
 * card itself is enrolled under that number on the machine's Register screen,
 * and the machine tells us (App\Support\BiometricFk::keepEnrolment).
 *
 * Otherwise the card is tapped on a USB card reader (which types the card's
 * number and Enter into the box) and goes to the school's machines with them
 * (App\Support\Biometric::assignCard). Shown only while the school has
 * biometric attendance on.
 */
class BiometricCard extends Component
{
    public string $holderType = 'student';   // student | teacher
    public int $holderId = 0;

    public bool $listening = false;
    public string $cardInput = '';

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
        $this->listening = true;
    }

    public function cancel(): void
    {
        $this->listening = false;
        $this->cardInput = '';
        $this->resetErrorBag();
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
            'number'  => $machine ? BiometricFk::panelNumber($this->orgId(), $this->holderType, $this->holderId) : null,
        ]);
    }
}
