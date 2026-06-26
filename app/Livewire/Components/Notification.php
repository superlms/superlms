<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notification extends Component
{
    /** IDs of notifications selected via checkbox. */
    public array $selected = [];

    /** Recent notifications for the logged-in user (guarded — empty if table absent). */
    public function getItemsProperty()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        try {
            return $user->notifications()->latest()->limit(50)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function markAllAsRead(): void
    {
        try {
            Auth::user()?->unreadNotifications->markAsRead();
        } catch (\Throwable $e) {
            // table may not exist yet — ignore
        }

        $this->dispatch('notifications-updated');
    }

    public function deleteOne(string $id): void
    {
        try {
            Auth::user()?->notifications()->whereKey($id)->delete();
        } catch (\Throwable $e) {
            // ignore
        }

        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->dispatch('notifications-updated');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        try {
            Auth::user()?->notifications()->whereKey($this->selected)->delete();
        } catch (\Throwable $e) {
            // ignore
        }

        $this->selected = [];
        $this->dispatch('notifications-updated');
    }

    public function deleteAll(): void
    {
        try {
            Auth::user()?->notifications()->delete();
        } catch (\Throwable $e) {
            // ignore
        }

        $this->selected = [];
        $this->dispatch('notifications-updated');
    }

    public function render()
    {
        return view('livewire.components.notification', [
            'items' => $this->items,
        ]);
    }
}
