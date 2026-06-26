<?php

namespace App\Livewire\SuperAdmin\Concerns;

use App\Models\WebsitePage;

/**
 * Shared behaviour for the super-admin editors of the dynamic marketing
 * pages (why-us, services, careers, become-executive, blogs, faqs).
 *
 * A consuming component must:
 *   - set $slug (e.g. 'faqs') in mount() before calling loadPage()
 *   - implement defaultMeta(): array  — the blank/initial structure
 *   - implement rowTemplates(): array — a blank row per repeatable key,
 *     e.g. ['items' => ['icon' => '', 'title' => '', 'desc' => '']]
 */
trait ManagesWebsitePage
{
    public string $slug = '';

    /** The page's full metadata, bound to the form. */
    public array $meta = [];

    public string $activeTab = 'edit';

    /** Pending delete confirmation: ['key' => string, 'index' => int]. */
    public ?array $pendingDelete = null;

    abstract protected function defaultMeta(): array;

    abstract protected function rowTemplates(): array;

    protected function loadPage(): void
    {
        $stored     = WebsitePage::meta($this->slug) ?? [];
        $this->meta = array_replace_recursive($this->defaultMeta(), $stored);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ─── Repeatable rows ──────────────────────────────────────────────

    public function addRow(string $key): void
    {
        $this->meta[$key][] = $this->rowTemplates()[$key] ?? [];
    }

    public function confirmRemoveRow(string $key, int $index): void
    {
        $this->pendingDelete = ['key' => $key, 'index' => $index];
    }

    public function cancelRemoveRow(): void
    {
        $this->pendingDelete = null;
    }

    public function executeRemoveRow(): void
    {
        if ($this->pendingDelete) {
            $key   = $this->pendingDelete['key'];
            $index = $this->pendingDelete['index'];

            if (isset($this->meta[$key][$index]) && count($this->meta[$key]) > 1) {
                unset($this->meta[$key][$index]);
                $this->meta[$key] = array_values($this->meta[$key]);
            }
        }
        $this->pendingDelete = null;
    }

    // ─── Persist ──────────────────────────────────────────────────────

    public function save(): void
    {
        if (method_exists($this, 'rules') || property_exists($this, 'rules')) {
            $this->validate();
        }

        WebsitePage::updateOrCreate(
            ['slug' => $this->slug],
            ['metadata' => $this->meta, 'last_updated' => now()->toDateString()],
        );

        $this->notification()->success('Saved', 'Page content updated successfully!');
    }
}
