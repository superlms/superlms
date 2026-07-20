<?php

namespace App\Livewire\Admin;

use App\Models\SchoolWebsite;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Admin-side editor for the school's public website CONTENT.
 *
 * The school admin manages all the marketing data here (brand, hero, about,
 * leadership, facilities, admissions, gallery, …). It is stored in the same
 * SchoolWebsite `content` JSON the template reads, so every save reflects on
 * the live site immediately. Pages, theme, custom domain and publishing stay
 * with the super-admin (SchoolWebsiteBuilder) and are NOT touched here.
 */
class WebsiteData extends Component
{
    use WireUiActions;

    public $organization;

    public array  $form              = [];   // scalar content fields
    public array  $classes           = [];   // [{title,age,time,capacity,image}]
    public array  $team              = [];   // [{name,role,photo}]
    public array  $leadership        = [];   // [{name,role,photo,message}]
    public array  $facilities        = [];   // [{icon,title,desc}]
    public array  $whyUs             = [];   // [{icon,title,desc}]
    public array  $stats             = [];   // [{value,label}]
    public array  $admissionSteps    = [];   // [{title,desc}]
    public array  $documentsRequired = [];   // [{text}]
    public array  $admissionRules    = [];   // [{text}]
    public array  $gallery           = [];   // [{image,caption}]
    public string $activeTab         = 'details';

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

    public function mount(): void
    {
        // Always scope to the signed-in admin's own organization.
        $this->organization = auth()->user()->organization?->load('schoolInfo.managementTeam');

        $website = SchoolWebsite::firstOrNew(['organization_id' => $this->organization?->id]);
        $website->setRelation('organization', $this->organization);

        $content = $website->resolvedContent();

        foreach ($this->scalarKeys as $k) {
            $this->form[$k] = (string) ($content[$k] ?? '');
        }

        foreach ($this->listContentKey as $prop => $key) {
            $this->{$prop} = array_values($content[$key] ?? []);
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
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

    // ─── Save (content only — never touches pages/theme/domain/status) ──

    public function save(): void
    {
        if (!$this->organization) {
            $this->notification()->error('No organization', 'Could not resolve your school.');
            return;
        }

        $this->validate(
            ['form.school_name' => 'required|string|max:255'],
            ['form.school_name.required' => 'School name is required.']
        );

        $content = $this->form;
        foreach ($this->listContentKey as $prop => $key) {
            $content[$key] = array_values($this->{$prop});
        }

        // updateOrCreate with only template + content leaves the super-admin's
        // pages, theme, domain and publish status untouched.
        SchoolWebsite::updateOrCreate(
            ['organization_id' => $this->organization->id],
            ['template' => 'kider', 'content' => $content],
        );

        $this->notification()->success('Saved', 'Website content updated — it is now live on your site.');
    }

    public function render()
    {
        return view('livewire.admin.website-data');
    }
}
