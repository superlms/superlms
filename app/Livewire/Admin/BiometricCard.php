<?php

namespace App\Livewire\Admin;

use App\Support\Biometric;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The 125 kHz card of the student or teacher open in a View panel: assigned
 * by tapping it on a USB card reader (which types the card's number and
 * Enter into the box), changed or removed. The card goes to the school's
 * machines with them (App\Support\Biometric::assignCard). Shown only while
 * the school has biometric attendance on.
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
        return view('livewire.admin.biometric-card', [
            'on'   => Biometric::enabled($this->orgId()),
            'card' => Biometric::cardOf($this->holderType, $this->holderId),
        ]);
    }
}
