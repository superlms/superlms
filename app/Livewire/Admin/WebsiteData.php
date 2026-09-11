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

    /** Generic content pages: slug => {heading, body, image}. */
    public array  $pages   = [];
    /** Document tables: slug => [{title,file,date}]. */
    public array  $docs    = [];

    /** Which content page / document table the editor is currently on. */
    public string $pageSlug = '';
    public string $docSlug  = 'disclosures';

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
        'results'           => ['year' => '', 'appeared' => '', 'passed' => ''],
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
        'results'           => 'results',
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

        // Seed a row per content page so every page has a form to fill in,
        // and per document table so the CBSE checklist is there to upload against.
        $this->pages = $content['pages'] ?? [];
        foreach (static::contentPageSlugs() as $slug) {
            $this->pages[$slug] = [
                'heading' => $this->pages[$slug]['heading'] ?? SchoolWebsite::allPages()[$slug] ?? '',
                'body'    => $this->pages[$slug]['body'] ?? '',
                'image'   => $this->pages[$slug]['image'] ?? '',
            ];
        }
        $this->pageSlug = array_key_first($this->pages) ?: '';

        $this->docs = $content['documents'] ?? [];
        foreach (static::documentPageSlugs() as $slug) {
            $rows = $this->docs[$slug] ?? [];
            if (empty($rows) && $slug === 'disclosures') {
                $rows = SchoolWebsite::defaultDisclosures();
            }
            $this->docs[$slug] = array_values($rows);
        }
    }

    /** Page slugs the template renders through the generic content blade. */
    public static function contentPageSlugs(): array
    {
        return static::slugsOfType('content');
    }

    /** Page slugs the template renders as a document table. */
    public static function documentPageSlugs(): array
    {
        return static::slugsOfType('documents');
    }

    private static function slugsOfType(string $type): array
    {
        $out = [];
        foreach (SchoolWebsite::pageGroups() as $pages) {
            foreach ($pages as $slug => [$label, $t]) {
                if ($t === $type) {
                    $out[] = $slug;
                }
            }
        }
        return $out;
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

    // ─── Document tables (one list per document page) ──────────────────

    public function addDocRow(string $slug): void
    {
        $this->docs[$slug][] = ['title' => '', 'file' => '', 'date' => ''];
    }

    public function removeDocRow(string $slug, int $i): void
    {
        if (isset($this->docs[$slug][$i])) {
            unset($this->docs[$slug][$i]);
            $this->docs[$slug] = array_values($this->docs[$slug]);
        }
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

        // Drop content pages left completely blank so the template keeps
        // showing its "being updated" note rather than an empty heading.
        $content['pages'] = array_filter(
            $this->pages,
            fn ($p) => trim((string) ($p['body'] ?? '')) !== '' || trim((string) ($p['image'] ?? '')) !== ''
        );

        $content['documents'] = [];
        foreach ($this->docs as $slug => $rows) {
            $rows = array_values(array_filter($rows, fn ($r) => trim((string) ($r['title'] ?? '')) !== ''));
            if ($rows) {
                $content['documents'][$slug] = $rows;
            }
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
