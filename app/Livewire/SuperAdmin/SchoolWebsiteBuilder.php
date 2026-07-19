<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\SchoolWebsite;
use Illuminate\Validation\Rule;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Super-admin builder for a single school's public website.
 * Auto-fetches the organization's details, lets the admin fill in the rest,
 * choose pages + colour theme, set the custom domain and publish.
 */
class SchoolWebsiteBuilder extends Component
{
    use WireUiActions;

    public Organization $organization;

    public array  $form         = [];   // scalar content fields
    public array  $classes      = [];   // [{title,age,time,capacity}]
    public array  $team         = [];   // [{name,role,photo}]
    public array  $leadership   = [];   // [{name,role,photo,message}]
    public array  $facilities   = [];   // [{icon,title,desc}]
    public array  $whyUs        = [];   // [{icon,title,desc}]
    public array  $stats        = [];   // [{value,label}]
    public array  $admissionSteps    = []; // [{title,desc}]
    public array  $documentsRequired = []; // [{text}]
    public array  $admissionRules    = []; // [{text}]
    public array  $gallery      = [];   // [{image,caption}]
    public array  $pages        = [];   // enabled page slugs
    public string $theme_preset = 'sunset';
    public string $primary      = '';
    public string $domain       = '';
    public bool   $status       = false;
    public string $activeTab    = 'details';

    /** Scalar content fields managed by the form. */
    private array $scalarKeys = [
        'school_name', 'tagline', 'motto', 'medium', 'board', 'affiliation_no', 'school_code', 'logo',
        'hero_title', 'hero_subtitle',
        'about_heading', 'about_text', 'about_text2', 'history_text', 'philosophy', 'vision', 'mission',
        'admission_intro', 'admission_session', 'fee_note', 'curriculum_text',
        'cta_heading', 'cta_text',
        'phone', 'email', 'address',
        'facebook', 'instagram', 'youtube', 'twitter', 'telegram',
    ];

    /** Repeatable list fields: state property => [row template]. */
    private array $listFields = [
        'classes'           => ['title' => '', 'age' => '', 'time' => '', 'capacity' => '', 'image' => ''],
        'team'              => ['name' => '', 'role' => '', 'photo' => ''],
        'leadership'        => ['name' => '', 'role' => '', 'photo' => '', 'message' => ''],
        'facilities'        => ['icon' => '', 'title' => '', 'desc' => ''],
        'whyUs'             => ['icon' => '', 'title' => '', 'desc' => ''],
        'stats'             => ['value' => '', 'label' => ''],
        'admissionSteps'    => ['title' => '', 'desc' => ''],
        'documentsRequired' => ['text' => ''],
        'admissionRules'    => ['text' => ''],
        'gallery'           => ['image' => '', 'caption' => ''],
    ];

    /** Map builder state property => content JSON key (camel → snake). */
    private array $listContentKey = [
        'classes'           => 'classes',
        'team'              => 'team',
        'leadership'        => 'leadership',
        'facilities'        => 'facilities',
        'whyUs'             => 'why_us',
        'stats'             => 'stats',
        'admissionSteps'    => 'admission_steps',
        'documentsRequired' => 'documents_required',
        'admissionRules'    => 'admission_rules',
        'gallery'           => 'gallery',
    ];

    public function mount(Organization $organization): void
    {
        $this->organization = $organization->load('schoolInfo.managementTeam');

        $website = SchoolWebsite::firstOrNew(['organization_id' => $organization->id]);
        $website->setRelation('organization', $this->organization);

        $content = $website->resolvedContent();

        foreach ($this->scalarKeys as $k) {
            $this->form[$k] = (string) ($content[$k] ?? '');
        }

        foreach ($this->listContentKey as $prop => $key) {
            $this->{$prop} = array_values($content[$key] ?? []);
        }

        $this->pages = $website->exists
            ? $website->enabledPages()
            : array_keys(SchoolWebsite::allPages());

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

    public function togglePage(string $slug): void
    {
        if ($slug === 'home') {
            return; // home is mandatory
        }
        if (in_array($slug, $this->pages, true)) {
            $this->pages = array_values(array_diff($this->pages, [$slug]));
        } else {
            $this->pages[] = $slug;
        }
    }

    // ─── Repeatable rows (generic) ────────────────────────────────────

    /** Append a blank row to any list field (e.g. addRow('facilities')). */
    public function addRow(string $list): void
    {
        if (!isset($this->listFields[$list])) {
            return;
        }
        $this->{$list}[] = $this->listFields[$list];
    }

    /** Remove a row from any list field and re-index. */
    public function removeRow(string $list, int $i): void
    {
        if (!isset($this->listFields[$list]) || !isset($this->{$list}[$i])) {
            return;
        }
        $rows = $this->{$list};
        unset($rows[$i]);
        $this->{$list} = array_values($rows);
    }

    // ─── Save ─────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'form.school_name' => 'required|string|max:255',
            'domain'           => [
                'nullable', 'string', 'max:255',
                'regex:/^(?!https?:\/\/)([a-z0-9-]+\.)+[a-z]{2,}$/i',
                Rule::unique('school_websites', 'domain')->ignore($this->organization->id, 'organization_id'),
            ],
            'theme_preset'     => 'required|string',
            'primary'          => 'nullable|regex:/^#([0-9a-fA-F]{6})$/',
        ], [
            'domain.regex'  => 'Enter a valid domain like myschool.com (no http://).',
            'domain.unique' => 'This domain is already assigned to another school.',
        ]);

        $content = $this->form;
        foreach ($this->listContentKey as $prop => $key) {
            $content[$key] = array_values($this->{$prop});
        }

        SchoolWebsite::updateOrCreate(
            ['organization_id' => $this->organization->id],
            [
                'domain'   => $this->domain ?: null,
                'template' => 'kider',
                'theme'    => ['preset' => $this->theme_preset, 'primary' => $this->primary ?: null],
                'pages'    => array_values(array_unique(array_merge(['home'], $this->pages))),
                'content'  => $content,
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
            'allPages'     => SchoolWebsite::allPages(),
            'themePresets' => SchoolWebsite::themePresets(),
        ]);
    }
}
