<?php

namespace App\Livewire\Admin;

use App\Models\SchoolWebsite;
use App\Support\MapLink;
use App\Support\SchoolSiteData;
use App\Support\WebsitePages;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

/**
 * More → Website Data: the school fills in its website page by page.
 *
 * It opens on the site's menus as tiles in Quick Links' look (School —
 * name, logo, contact — first, then the menus the site shows its pages
 * under). A menu picked shows its pages as tiles, and a page picked opens
 * full width, in the About App editor's look, on the blocks that page shows
 * (WebsitePages): text, photos, files, lists, the map, and what the site
 * reads live from the school's own records (classes, fees, rules, routes,
 * events, the School Profile's texts). Back steps out one level at a time.
 * Photos and files upload as soon as they are picked; Save writes the
 * page's blocks into the site's content and they are live.
 *
 * Only content is written here — the super admin's template, pages, theme,
 * domain and publish switch are left alone — and only what differs from the
 * school's profile, so the site keeps following its logo, phone and the rest.
 */
class WebsiteData extends Component
{
    use WireUiActions, WithFileUploads;

    public $organization;

    /** The menu whose pages show as tiles ('' for the menus); a step for Back. */
    #[Url(as: 'menu', history: true, except: '')]
    public string $group = '';

    /** The page open ('' for none, 'details' for School Details); a step for Back. */
    #[Url(as: 'edit', history: true, except: '')]
    public string $open = '';

    /** The open page's blocks' values: block id => value. */
    public array $data = [];

    /** Files picked on the open page: uploaded and placed as soon as they arrive. */
    public array $uploads = [];

    /** Something on the open page changed since it was opened or saved. */
    public bool $dirty = false;

    public function mount(): void
    {
        // Always scope to the signed-in admin's own organization.
        $this->organization = auth()->user()->organization?->load('schoolInfo.managementTeam');

        if ($this->open !== '') {
            $this->openPage($this->open);
        } elseif ($this->group !== '' && ! isset($this->menus()[$this->group])) {
            $this->group = '';
        }
    }

    /** The school's site, read once a request. */
    private ?SchoolWebsite $site = null;

    private function site(): SchoolWebsite
    {
        if (! $this->site) {
            $this->site = SchoolWebsite::firstOrNew(['organization_id' => $this->organization?->id]);
            $this->site->setRelation('organization', $this->organization);
        }

        return $this->site;
    }

    /** The school's own records the site reads (classes, fees, rules), read once a request. */
    private ?SchoolSiteData $lms = null;

    private function lms(): SchoolSiteData
    {
        return $this->lms ??= new SchoolSiteData($this->organization?->id);
    }

    /** The site's pages with whether each is filled in, worked out once a request. */
    private ?array $listing = null;

    private function listing(): array
    {
        return $this->listing ??= WebsitePages::listing($this->site(), $this->lms());
    }

    /**
     * The menus as the chips show them: School (School Details) first, then
     * the site's own menus with their pages switched on — menu => [slug => row].
     */
    private function menus(): array
    {
        $listing = $this->listing();

        return [WebsitePages::DETAILS_GROUP => [WebsitePages::DETAILS => array_merge($listing['details'], [
            'summary' => 'Name, logo, phone, address and social links — on every page',
        ])]] + $listing['groups'];
    }

    private function page(): ?array
    {
        return $this->open === '' ? null : WebsitePages::page($this->open, $this->site()->templateKey());
    }

    // ─── Menus ↔ pages ───────────────────────────────────────────────────

    /**
     * A menu's chip: its pages show as chips under the menus. A menu of one
     * page opens that page straight away; another menu's page closes.
     */
    public function openGroup(string $name): void
    {
        $menus = $this->menus();
        if (! isset($menus[$name])) {
            return;
        }

        $this->group = $name;
        if (count($menus[$name]) === 1) {
            $this->openPage((string) array_key_first($menus[$name]));
        } elseif ($this->open !== '' && ! isset($menus[$name][$this->open])) {
            $this->closePage();
        }
    }

    public function openPage(string $slug): void
    {
        $page = WebsitePages::page($slug, $this->site()->templateKey());
        if (! $page) {
            $this->closePage();

            return;
        }

        $this->open    = $slug;
        $this->group   = (string) $page['group'];
        $this->data    = WebsitePages::values($page['blocks'], $this->site());
        $this->uploads = [];
        $this->dirty   = false;
        $this->resetErrorBag();
    }

    /**
     * Back, a step at a time: from a page to its menu's pages (or to the
     * menus, for a menu of one page), from a menu's pages to the menus.
     */
    public function back(): void
    {
        if ($this->open !== '') {
            if (count($this->menus()[$this->group] ?? []) <= 1) {
                $this->group = '';
            }
            $this->closePage();
        } else {
            $this->group = '';
        }
    }

    public function closePage(): void
    {
        $this->open    = '';
        $this->data    = [];
        $this->uploads = [];
        $this->dirty   = false;
        $this->resetErrorBag();
    }

    /** The browser's Back / Forward between the menus and a page. */
    public function updatedOpen(): void
    {
        $this->open === '' ? $this->closePage() : $this->openPage($this->open);
    }

    /** The browser's Back / Forward between the menus. */
    public function updatedGroup(): void
    {
        $menus = $this->menus();
        if (! isset($menus[$this->group])) {
            $this->group = '';
        }
        if ($this->open !== '' && ! isset($menus[$this->group][$this->open])) {
            $this->closePage();
        }
    }

    public function updated(string $name): void
    {
        if (str_starts_with($name, 'data.')) {
            $this->dirty = true;
        }
        // Coordinates typed in replace a pasted embed.
        if (in_array($name, ['data.location.lat', 'data.location.lng'], true)) {
            $this->data['location']['embed'] = '';
        }
    }

    // ─── Lists, photos ───────────────────────────────────────────────────

    private function listBlock(string $id): ?array
    {
        $b = ($page = $this->page()) ? WebsitePages::block($page, $id) : null;

        return $b && in_array($b['type'], ['list', 'images'], true) ? $b : null;
    }

    public function addRow(string $id): void
    {
        if (($b = $this->listBlock($id)) && $b['type'] === 'list') {
            $this->data[$id][] = WebsitePages::row($b, []);
            $this->dirty = true;
        }
    }

    public function removeRow(string $id, int $i): void
    {
        if ($this->listBlock($id) && isset($this->data[$id][$i])) {
            array_splice($this->data[$id], $i, 1);
            $this->dirty = true;
        }
    }

    public function moveRow(string $id, int $i, int $by): void
    {
        $to = $i + $by;
        if ($this->listBlock($id) && isset($this->data[$id][$i], $this->data[$id][$to])) {
            [$this->data[$id][$i], $this->data[$id][$to]] = [$this->data[$id][$to], $this->data[$id][$i]];
            $this->dirty = true;
        }
    }

    /** Empty one photo or file: "logo", or "hero_slides.2.image". */
    public function clearValue(string $path): void
    {
        $page = $this->page();
        if ($page && WebsitePages::block($page, explode('.', $path)[0]) && is_string(data_get($this->data, $path))) {
            data_set($this->data, $path, '');
            $this->dirty = true;
        }
    }

    // ─── Uploads ─────────────────────────────────────────────────────────

    /**
     * A photo or file picked: "logo" (a photo block), "photos" or "gallery"
     * (several, for a photos block or a list's photo column) or
     * "hero_slides.2.image" (a list row's photo or file). It is checked,
     * stored and put in its place; the page is saved with Save.
     */
    public function updatedUploads(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        $page  = $this->page();
        $b     = $page ? WebsitePages::block($page, $parts[0]) : null;

        // The column a list row's upload goes to, or a list's bulk photo column.
        $kind = match (true) {
            ! $b                    => null,
            count($parts) === 3     => isset($this->data[$parts[0]][(int) $parts[1]]) ? ($b['fields'][$parts[2]][0] ?? null) : null,
            $b['type'] === 'list'   => isset($b['bulk']) ? 'image' : null,
            default                 => $b['type'],
        };
        $files = array_values(array_filter(is_array($value) ? $value : [$value], fn ($f) => $f instanceof TemporaryUploadedFile));
        $this->uploads = [];

        if (! in_array($kind, ['image', 'images', 'file'], true) || ! $files) {
            return;
        }

        $rule = $kind === 'file'
            ? ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480']
            : ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'];
        $urls = [];
        foreach ($files as $file) {
            if (Validator::make(['f' => $file], ['f' => $rule])->fails()) {
                $this->addError("uploads.{$key}", $kind === 'file'
                    ? 'Pick a PDF or a photo (JPG, PNG, WebP) under 20 MB.'
                    : 'Pick a photo (JPG, PNG, WebP or GIF) under 5 MB.');
                continue;
            }
            $urls[] = $this->store($file);
        }
        if (! $urls) {
            return;
        }

        $id = $parts[0];
        if (count($parts) === 3) {
            data_set($this->data, $key, $urls[0]);
        } elseif ($b['type'] === 'list') {
            foreach ($urls as $url) {
                $this->data[$id][] = array_merge(WebsitePages::row($b, []), [$b['bulk'] => $url]);
            }
        } elseif ($b['type'] === 'images') {
            $this->data[$id] = array_merge($this->data[$id] ?? [], $urls);
        } else {
            $this->data[$id] = $urls[0];
        }
        $this->dirty = true;
        $this->resetErrorBag("uploads.{$key}");
    }

    private function store(TemporaryUploadedFile $file): string
    {
        $path = $file->store('admin/website/' . $this->organization->id, 's3');
        try {
            Storage::disk('s3')->setVisibility($path, 'public');
        } catch (\Throwable) {
            // A bucket with ACLs off serves by its policy instead.
        }

        return Storage::disk('s3')->url($path);
    }

    // ─── The map ─────────────────────────────────────────────────────────

    /** A pasted Google Maps link (or coordinates) becomes the school's pin. */
    public function setMapLink(): void
    {
        $found = MapLink::parse((string) ($this->data['location']['link'] ?? ''));
        if (! $found) {
            $this->addError('data.location.link', "Couldn't find a place in that. In Google Maps, open your school, tap Share → Copy link, and paste it here.");

            return;
        }

        $this->data['location'] = array_merge($this->data['location'], [
            'lat'   => $found['lat'] ?? '',
            'lng'   => $found['lng'] ?? '',
            'embed' => $found['embed'] ?? '',
            'link'  => '',
        ]);
        $this->dirty = true;
        $this->resetErrorBag('data.location.link');
    }

    /** The phone's own location (the browser's), standing at the school. */
    public function setPin(float $lat, float $lng): void
    {
        if (! isset($this->data['location']) || ! ($pin = MapLink::pin($lat, $lng))) {
            return;
        }
        $this->data['location'] = array_merge($this->data['location'], $pin, ['embed' => '']);
        $this->dirty = true;
    }

    /** Back to the map searched from the address. */
    public function clearPin(): void
    {
        if (isset($this->data['location'])) {
            $this->data['location'] = array_merge($this->data['location'], ['lat' => '', 'lng' => '', 'embed' => '']);
            $this->dirty = true;
        }
    }

    // ─── Save ────────────────────────────────────────────────────────────

    public function save(): void
    {
        if (! $this->organization) {
            $this->notification()->error('No organization', 'Could not resolve your school.');

            return;
        }
        $site = $this->site();
        $page = $this->page();
        if (! $page) {
            return;
        }

        $rules = $messages = [];
        foreach ($page['blocks'] as $b) {
            if (! empty($b['required'])) {
                $rules["data.{$b['id']}"] = 'required|string|max:255';
                $messages["data.{$b['id']}.required"] = "{$b['label']} is required.";
            }
        }
        if ($rules) {
            $this->validate($rules, $messages);
        }

        $content = WebsitePages::apply($page['blocks'], $this->data, $site->content ?? []);
        $site->content = $site->overridesOnly($content);
        $site->save();

        $this->dirty = false;
        $this->notification()->success('Saved', "{$page['label']} is updated on your website.");
    }

    public function render()
    {
        $site = $this->site();
        $page = $this->page();
        $live = $site->status && $site->domain;

        return view('livewire.admin.website-data', [
            'site'    => $site,
            'page'    => $page,
            'menus'   => $this->menus(),
            'off'     => $this->listing()['off'],
            'lms'     => $this->lms(),
            'pageUrl' => $live && $page
                ? 'https://' . $site->domain . '/' . (in_array($this->open, ['home', WebsitePages::DETAILS], true) ? '' : $this->open)
                : null,
            'mapSrc'  => isset($this->data['location']) ? SchoolWebsite::mapSrc([
                'school_name' => $site->resolvedContent()['school_name'] ?? '',
                'address'     => $this->data['location']['address'] ?? '',
                'map_lat'     => $this->data['location']['lat'] ?? '',
                'map_lng'     => $this->data['location']['lng'] ?? '',
                'map_embed'   => $this->data['location']['embed'] ?? '',
            ]) : '',
        ]);
    }
}
