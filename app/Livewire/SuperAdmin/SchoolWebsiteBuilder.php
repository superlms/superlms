<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\SchoolWebsite;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Super-admin builder for a single school's public website.
 *
 * The super-admin ONLY owns the site "shell": which pages are enabled, the
 * colour theme, the custom domain and the publish switch. All the marketing
 * CONTENT (brand, about, leadership, facilities, admissions, gallery…) is
 * managed by the school admin under More → Website Data and stored in the same
 * SchoolWebsite `content` JSON, so this screen never writes `content`.
 */
class SchoolWebsiteBuilder extends Component
{
    use WireUiActions;

    public Organization $organization;

    public array  $pages        = [];   // enabled page slugs
    public string $template     = 'kider';
    public string $theme_preset = 'sunset';
    public string $primary      = '';
    public string $domain       = '';
    public bool   $status       = false;
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'pages')]
    public string $activeTab    = 'pages';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;

        $website = SchoolWebsite::firstOrNew(['organization_id' => $organization->id]);

        $this->pages = $website->exists
            ? $website->enabledPages()
            : array_keys(SchoolWebsite::allPages());

        $this->template = $website->exists ? $website->templateKey() : 'kider';

        $theme = $website->theme ?? [];
        $this->theme_preset = $theme['preset'] ?? 'sunset';
        $this->primary      = $theme['primary'] ?? '';
        $this->domain       = $website->domain ?? '';
        $this->status       = (bool) $website->status;
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Picking Classic on the default colours moves to its red-and-green
     * tricolour preset; a preset or colour picked on purpose is kept.
     */
    public function pickTemplate(string $key): void
    {
        if (! array_key_exists($key, SchoolWebsite::templates())) {
            return;
        }
        $this->template = $key;
        if ($key === 'classic' && $this->theme_preset === 'sunset' && $this->primary === '') {
            $this->theme_preset = 'tricolour';
        }
    }

    /** The Template radio (wire:model.live) goes through the same pick. */
    public function updatedTemplate(string $key): void
    {
        $this->pickTemplate($key);
    }

    // Pages are ticked like the school's Modules (wire:model on the list);
    // Home is always on.

    public function enableAllPages(): void
    {
        $this->pages = array_keys(SchoolWebsite::allPages());
    }

    public function disableAllPages(): void
    {
        $this->pages = ['home'];
    }

    // ─── Save (shell only — pages / theme / domain / publish) ──────────────

    public function save(): void
    {
        $this->validate([
            'domain'           => [
                'nullable', 'string', 'max:255',
                'regex:/^(?!https?:\/\/)([a-z0-9-]+\.)+[a-z]{2,}$/i',
                Rule::unique('school_websites', 'domain')->ignore($this->organization->id, 'organization_id'),
            ],
            'template'         => ['required', Rule::in(array_keys(SchoolWebsite::templates()))],
            'theme_preset'     => 'required|string',
            'primary'          => 'nullable|regex:/^#([0-9a-fA-F]{6})$/',
        ], [
            'domain.regex'  => 'Enter a valid domain like myschool.com (no http://).',
            'domain.unique' => 'This domain is already assigned to another school.',
        ]);

        // Content (brand, about, leadership, facilities, admissions, gallery…) is
        // managed by the school admin under More → Website Data. The builder only
        // owns pages, theme, domain and publishing, so we never write the
        // `content` column here — that would wipe the admin's data.
        SchoolWebsite::updateOrCreate(
            ['organization_id' => $this->organization->id],
            [
                'domain'   => $this->domain ?: null,
                'template' => $this->template,
                'theme'    => ['preset' => $this->theme_preset, 'primary' => $this->primary ?: null],
                'pages'    => array_values(array_unique(array_merge(['home'], $this->pages))),
                'status'   => $this->status,
            ],
        );

        $this->notification()->success(
            'Saved',
            $this->status ? 'Website saved & published.' : 'Website saved as draft.'
        );
    }

    public function render()
    {
        return view('livewire.super-admin.school-website-builder', [
            'templates'    => SchoolWebsite::templates(),
            'allPages'     => SchoolWebsite::allPages(),
            'pageGroups'   => SchoolWebsite::pageGroups(),
            'themePresets' => SchoolWebsite::themePresets(),
        ]);
    }
}
