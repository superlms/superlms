<?php

namespace App\Console\Commands;

use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Support\DefaultSchoolRules;
use App\Support\DemoWebsiteContent;
use App\Support\SchoolSiteData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fill a school's whole website with sample data, so the site can be seen as
 * it looks with real content — every page of the Classic template switched
 * on, and every page given text, photos, lists and documents
 * (App\Support\DemoWebsiteContent).
 *
 * Besides Website Data, the site reads some pages live from the school's own
 * records; those are filled only where the school has none, never changed:
 *   - the School Profile's about text, vision, mission, values and goals, and its team;
 *   - Rules & Regulations (the standard set, and the timings as additional information);
 *   - Transport routes;
 *   - the session's calendar events.
 * Classes and the fee structure are never written: they drive the school's
 * students and fees.
 *
 * Website Data's own fields are filled where they are empty; --force writes
 * them all. The disclosure, fee and calendar documents are generated as PDFs,
 * marked as samples, and stored where Website Data keeps its uploads.
 */
class SeedDemoWebsite extends Command
{
    protected $signature = 'lms:seed-demo-website
                            {--org= : Only this organization id}
                            {--demo : The demo school ("The Demo School")}
                            {--force : Overwrite what Website Data already holds}
                            {--no-pdfs : Do not generate the sample documents}
                            {--rename : Only put the website\'s own name where the sample text and documents have the school\'s account name}';

    protected $description = 'Fill a school\'s website (and the records it reads) with sample data';

    public const DEMO_SCHOOL = 'the demo school';

    private const EVENT_COLOURS = ['holiday' => '#ef4444', 'exam' => '#f59e0b', 'sports' => '#22c55e', 'meeting' => '#8b5cf6', 'event' => '#3b82f6'];

    public function handle(): int
    {
        if (! Schema::hasTable('school_websites') || ! Schema::hasTable('organizations')) {
            $this->warn('The website tables are missing — nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('org')) {
            $orgIds = [(int) $this->option('org')];
        } elseif ($this->option('demo')) {
            $orgIds = Organization::whereRaw('LOWER(TRIM(name)) = ?', [self::DEMO_SCHOOL])->pluck('id')->all();
            if (! $orgIds) {
                $this->line('No school named "The Demo School" — nothing to do.');
            }
        } else {
            $this->error('Pass --org=ID or --demo.');

            return self::FAILURE;
        }

        foreach ($orgIds as $orgId) {
            $this->seedOrganization((int) $orgId);
        }

        return self::SUCCESS;
    }

    private function seedOrganization(int $orgId): void
    {
        $org = Organization::find($orgId);
        if (! $org) {
            $this->warn("Organization {$orgId} not found — skipped.");

            return;
        }
        $this->info("[{$orgId}] {$org->name}");

        [$from, , $session] = SchoolSiteData::session();
        $name  = self::siteName($org);
        $board = trim((string) $org->education_board) ?: 'CBSE';

        if ($this->option('rename')) {
            $this->rename($org, $name, $board, $session);

            return;
        }

        $info = $this->profile($org, $name);
        $this->team($info);
        $this->rules($orgId);
        $this->transport($orgId);
        $this->events($orgId, (int) $from->year);

        $lms = new SchoolSiteData($orgId);
        $this->line('  Classes: ' . count($lms->classes()) . ' (from Standard — not seeded)');
        $this->line('  Fee structure: ' . count($lms->fees()) . ' class groups (from Fee Structure — not seeded)');

        $this->website($org, $name, $board, $session, $lms);
    }

    /**
     * The school's name as its website shows it — Website Data's School
     * name, else the school's own — so the sample text reads the same name
     * as the masthead above it.
     */
    public static function siteName(Organization $org): string
    {
        $site   = SchoolWebsite::where('organization_id', $org->id)->first();
        $stored = trim((string) (($site?->content ?? [])['school_name'] ?? ''));

        return $stored !== '' ? $stored : trim((string) $org->name);
    }

    /**
     * Sample text and documents written with the school's account name ("The
     * Demo School") read the website's own name instead: every text in
     * Website Data, the sample About text on the School Profile (only while
     * it is still the sample, word for word), and the sample PDFs, made again
     * where they are kept. Nothing else is touched.
     */
    private function rename(Organization $org, string $to, string $board, string $session): void
    {
        $from = trim((string) $org->name);
        $site = SchoolWebsite::where('organization_id', $org->id)->first();
        if (! $site || $from === '' || strcasecmp($from, $to) === 0) {
            $this->line('  The website goes by the school\'s own name — nothing to rename.');

            return;
        }
        $site->setRelation('organization', $org->loadMissing('schoolInfo'));

        $content = $site->content ?? [];
        $texts   = 0;
        array_walk_recursive($content, function (&$value, $key) use ($from, $to, &$texts) {
            if (is_string($value) && $key !== 'school_name' && stripos($value, $from) !== false) {
                $value = str_ireplace($from, $to, $value, $n);
                $texts += $n;
            }
        });

        $about = 0;
        if (Schema::hasTable('school_infos')) {
            $about = DB::table('school_infos')->where('organization_id', $org->id)
                ->where('about_school', DemoWebsiteContent::profile($from)['about_school'])
                ->update(['about_school' => DemoWebsiteContent::profile($to)['about_school'], 'updated_at' => now()]);
            $org->unsetRelation('schoolInfo');
            $site->setRelation('organization', $org->loadMissing('schoolInfo'));
        }

        $site->content = $site->overridesOnly($content);
        $site->save();

        // The sample PDFs carry the name in their heading: made again, in the same place.
        $docs = 0;
        if (! $this->option('no-pdfs')) {
            $lms  = new SchoolSiteData($org->id);
            $c    = $site->resolvedContent();
            $mine = '/admin/website/' . $org->id . '/demo/';
            foreach ($content['documents'] ?? [] as $slug => $rows) {
                foreach (is_array($rows) ? $rows : [] as $i => $row) {
                    if (str_contains((string) ($row['file'] ?? ''), $mine)) {
                        $url = $this->pdf($org, $to, $board, $session, (string) $slug, (int) $i, (string) ($row['title'] ?? ''), $c, $lms);
                        if ($url !== '') {
                            $content['documents'][$slug][$i]['file'] = $url;
                            $docs++;
                        }
                    }
                }
            }
            if ($docs) {
                $site->content = $site->overridesOnly($content);
                $site->save();
            }
        }

        $this->line("  Renamed \"{$from}\" to \"{$to}\": {$texts} in Website Data" . ($about ? ', the About text' : '') . ", {$docs} documents made again.");
    }

    // ─── The school's own records ───────────────────────────────────────

    /** The School Profile's texts, each only where the profile has none. */
    private function profile(Organization $org, string $name): ?SchoolInfo
    {
        if (! Schema::hasTable('school_infos')) {
            return null;
        }

        $info   = SchoolInfo::firstOrCreate(['organization_id' => $org->id]);
        $filled = [];
        foreach (DemoWebsiteContent::profile($name) as $column => $text) {
            if (Schema::hasColumn('school_infos', $column) && trim((string) $info->{$column}) === '') {
                $info->{$column} = $text;
                $filled[] = $column;
            }
        }
        $info->save();

        $this->line('  School Profile texts: ' . ($filled ? 'filled ' . implode(', ', $filled) : 'already written — kept'));

        return $info;
    }

    /** The School Profile's team, when it has nobody on it. */
    private function team(?SchoolInfo $info): void
    {
        if (! $info || ! Schema::hasTable('school_management_teams')) {
            return;
        }
        if (DB::table('school_management_teams')->where('school_info_id', $info->id)->exists()) {
            $this->line('  Team: already set — kept');

            return;
        }

        $now = now();
        DB::table('school_management_teams')->insert(array_map(fn ($m, $i) => [
            'school_info_id' => $info->id,
            'name'           => $m[0],
            'designation'    => $m[1],
            'photo_path'     => DemoWebsiteContent::portrait($m[2]),
            'sort_order'     => $i + 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ], DemoWebsiteContent::team(), array_keys(DemoWebsiteContent::team())));

        $this->line('  Team: added ' . count(DemoWebsiteContent::team()) . ' members');
    }

    /** Rules & Regulations: the standard set where there is none, and the timings as additional information. */
    private function rules(int $orgId): void
    {
        if (! Schema::hasTable('rules_and_regulations')) {
            return;
        }

        $row     = DB::table('rules_and_regulations')->where('organization_id', $orgId)->first();
        $content = $row ? (json_decode((string) $row->content, true) ?: []) : [];
        $changed = [];

        if (empty($content['sections'])) {
            $content['sections'] = DefaultSchoolRules::sections();
            $changed[] = 'rules';
        }
        if (empty($content['additional_info'])) {
            $content['additional_info'] = array_map(fn ($r) => ['key' => $r[0], 'value' => $r[1]], DemoWebsiteContent::rulesInfo());
            $changed[] = 'timings';
        }
        $content['files'] ??= [];

        if (! $changed) {
            $this->line('  Rules & Regulations: already written — kept');

            return;
        }

        $content['last_updated'] = now()->toDateTimeString();
        $values = ['content' => json_encode($content), 'updated_at' => now()];
        if ($row) {
            DB::table('rules_and_regulations')->where('id', $row->id)->update($values);
        } else {
            DB::table('rules_and_regulations')->insert($values + ['organization_id' => $orgId, 'user_id' => 0, 'created_at' => now()]);
        }

        $this->line('  Rules & Regulations: added ' . implode(' and ', $changed));
    }

    /** Transport routes, when the school runs none. */
    private function transport(int $orgId): void
    {
        if (! Schema::hasTable('transportations')) {
            return;
        }
        if (DB::table('transportations')->where('organization_id', $orgId)->where('is_active', true)->exists()) {
            $this->line('  Transport: routes already set — kept');

            return;
        }

        $has  = fn (string $col) => Schema::hasColumn('transportations', $col);
        $now  = now();
        $rows = [];
        foreach (DemoWebsiteContent::routes() as [$route, $vehicle, $pickup, $drop, $fee, $seats, $stops]) {
            $row = [
                'organization_id'  => $orgId,
                'route_name'       => $route,
                'driver_detail_id' => 0,
                'pickup_location'  => $stops[0],
                'drop_location'    => 'School',
                'stops'            => json_encode($stops),
                'monthly_fee'      => $fee,
                'capacity'         => $seats,
                'is_active'        => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            foreach (['vehicle_type' => $vehicle, 'route_group' => (string) Str::uuid(), 'pickup_time' => $pickup, 'drop_time' => $drop] as $col => $value) {
                if ($has($col)) {
                    $row[$col] = $value;
                }
            }
            $rows[] = $row;
        }
        DB::table('transportations')->insert($rows);

        $this->line('  Transport: added ' . count($rows) . ' routes');
    }

    /** This session's calendar, when it has no events (class periods aside). */
    private function events(int $orgId, int $year): void
    {
        if (! Schema::hasTable('time_tables')) {
            return;
        }

        [$from, $to] = SchoolSiteData::session();
        $exists = DB::table('time_tables')->where('organization_id', $orgId)
            ->where('event_type', '!=', 'class')
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->exists();
        if ($exists) {
            $this->line('  Calendar: this session has events already — kept');

            return;
        }

        $has  = fn (string $col) => Schema::hasColumn('time_tables', $col);
        $now  = now();
        $rows = [];
        foreach (DemoWebsiteContent::events($year) as [$date, $title, $type, $desc, $allDay, $start, $end]) {
            $row = [
                'organization_id' => $orgId,
                'title'           => $title,
                'description'     => $desc,
                'date'            => $date,
                'start_time'      => $start ? $start . ':00' : null,
                'end_time'        => $end ? $end . ':00' : null,
                'event_type'      => $type,
                'color'           => self::EVENT_COLOURS[$type] ?? '#3b82f6',
                'recurrence'      => 'none',
                'is_all_day'      => $allDay,
                'is_cancelled'    => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
            $rows[] = array_filter($row, fn ($col) => $has($col), ARRAY_FILTER_USE_KEY);
        }
        DB::table('time_tables')->insert($rows);

        $this->line('  Calendar: added ' . count($rows) . " events for {$year}-" . substr((string) ($year + 1), -2));
    }

    // ─── Website Data ───────────────────────────────────────────────────

    private function website(Organization $org, string $name, string $board, string $session, SchoolSiteData $lms): void
    {
        $site = SchoolWebsite::where('organization_id', $org->id)->first();
        if (! $site) {
            $site = SchoolWebsite::create(['organization_id' => $org->id, 'template' => SchoolWebsite::DEFAULT_TEMPLATE, 'status' => false]);
            $this->warn('  Website: none yet — created one, unpublished and with no domain (set them in Super Admin → School Website).');
        }
        $site->setRelation('organization', $org->loadMissing('schoolInfo'));

        $force    = (bool) $this->option('force');
        $stored   = $site->content ?? [];
        $resolved = $site->resolvedContent();
        $written  = 0;

        foreach (DemoWebsiteContent::content($name, $board, $session) as $key => $value) {
            if ($key === 'pages') {
                foreach ($value as $slug => $page) {
                    $cur = $stored['pages'][$slug] ?? [];
                    if ($force || trim(($cur['body'] ?? '') . ($cur['image'] ?? '') . ($cur['photos'] ?? '')) === '') {
                        $stored['pages'][$slug] = $page;
                        $written++;
                    }
                }
                continue;
            }
            if ($force || $this->blank($stored[$key] ?? null)) {
                $stored[$key] = $value;
                $written++;
            }
        }

        // Contact details come from the school's profile; filled only where it has none.
        foreach (['phone' => '98765 43210, 98765 43211', 'email' => 'office@example.com', 'address' => 'Civil Lines, Main Road, Near District Court'] as $key => $value) {
            if (trim((string) ($resolved[$key] ?? '')) === '' && $this->blank($stored[$key] ?? null)) {
                $stored[$key] = $value;
                $written++;
            }
        }

        $docs = $this->documents($org, $name, $board, $session, $stored, array_replace($resolved, array_filter($stored, fn ($v) => ! $this->blank($v))), $lms, $force);

        $before = $site->enabledPages();
        $site->pages   = array_keys(SchoolWebsite::allPages());
        $site->content = $site->overridesOnly($stored);
        $site->save();

        $on = count(array_diff($site->enabledPages(), $before));
        $this->line("  Website: {$written} fields filled, {$docs} documents, " . ($on ? "{$on} more pages switched on" : 'every page on')
            . ($site->domain ? " — https://{$site->domain}" : ' — no domain set') . ($site->status ? '' : ' (unpublished)'));
    }

    /** The document pages' rows, a sample PDF each; how many were written. */
    private function documents(Organization $org, string $name, string $board, string $session, array &$stored, array $c, SchoolSiteData $lms, bool $force): int
    {
        $count = 0;
        $pdfs  = ! $this->option('no-pdfs');

        foreach (DemoWebsiteContent::documentTitles($session) as $slug => $rows) {
            $cur = $stored['documents'][$slug] ?? [];
            if (! $force && array_filter($cur, fn ($r) => trim((string) ($r['file'] ?? '')) !== '')) {
                continue;
            }

            $out = [];
            foreach ($rows as $i => [$title, $date]) {
                $file = $pdfs ? $this->pdf($org, $name, $board, $session, $slug, $i, $title, $c, $lms) : '';
                $out[] = ['title' => $title, 'date' => $date, 'file' => $file];
                $count++;
            }
            $stored['documents'][$slug] = $out;
        }

        return $count;
    }

    /** One sample document as a PDF in the school's website uploads; its link, or '' when it can't be stored. */
    private function pdf(Organization $org, string $name, string $board, string $session, string $slug, int $i, string $title, array $content, SchoolSiteData $lms): string
    {
        $body = match (true) {
            str_contains($title, 'Fee Structure')       => $this->feeTable($lms),
            str_contains($title, 'Fee Payment')         => $this->feeSchedule(),
            str_contains($title, 'Academic Calendar')   => $this->eventTable($lms, null),
            str_contains($title, 'Examination Schedule') => $this->eventTable($lms, 'Exam'),
            str_contains($title, 'Result')              => $this->resultTable($content['results'] ?? []),
            default                                     => $this->detailTable($org, $name, $board, $content),
        };

        $esc  = fn ($v) => e((string) $v);
        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#222;margin:26px 34px}'
            . '.head{text-align:center;border-bottom:3px double #1f3a68;padding-bottom:10px;margin-bottom:16px}'
            . '.head h1{font-size:20px;color:#1f3a68;margin:0}.head p{margin:3px 0;color:#555}'
            . 'h2{font-size:15px;text-align:center;margin:6px 0 2px}.sub{text-align:center;color:#666;margin-bottom:14px}'
            . 'table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #bbb;padding:5px 7px;text-align:left;vertical-align:top}'
            . 'th{background:#eef2f8}.r{text-align:right}.note{margin-top:22px;padding:8px 10px;background:#fff7e6;border:1px solid #f2c46d;color:#7a5200;font-size:10px}'
            . '.sign{margin-top:46px;text-align:right}</style></head><body>'
            . '<div class="head"><h1>' . $esc($name) . '</h1>'
            . '<p>' . $esc(trim(($content['medium'] ?? '') . ' · Affiliated to ' . $board, ' ·')) . '</p>'
            . (trim((string) ($content['address'] ?? $org->address ?? '')) !== '' ? '<p>' . $esc($content['address'] ?? $org->address) . '</p>' : '')
            . '</div><h2>' . $esc($title) . '</h2><p class="sub">Session ' . $esc($session) . '</p>'
            . $body
            . '<div class="sign">Principal<br>' . $esc($name) . '</div>'
            . '<div class="note">Sample document — published on the school website for demonstration.</div>'
            . '</body></html>';

        try {
            $bytes = Pdf::loadHTML($html)->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
            $path  = 'admin/website/' . $org->id . '/demo/' . $slug . '-' . ($i + 1) . '-' . Str::slug(Str::limit($title, 40, '')) . '.pdf';
            Storage::disk('s3')->put($path, $bytes, ['ContentType' => 'application/pdf']);
            try {
                Storage::disk('s3')->setVisibility($path, 'public');
            } catch (\Throwable) {
                // A bucket with ACLs off serves by its policy instead.
            }

            return Storage::disk('s3')->url($path);
        } catch (\Throwable $e) {
            $this->warn("  Could not store \"{$title}\": " . Str::limit($e->getMessage(), 160));

            return '';
        }
    }

    private function detailTable(Organization $org, string $name, string $board, array $c): string
    {
        $rows = [
            'Name of the School' => $name,
            'Board'              => $board,
            'Affiliation No.'    => $c['affiliation_no'] ?? '',
            'School Code'        => $c['school_code'] ?? '',
            'Address'            => $c['address'] ?? $org->address ?? '',
            'Phone'              => $c['phone'] ?? $org->mobile_number ?? '',
            'Email'              => $c['email'] ?? $org->email ?? '',
            'Date of Issue'      => '1 April 2026',
            'Valid Up To'        => '31 March 2029',
        ];

        return '<p>This is a true copy of the document named above, kept on record at the school office and available for inspection by '
            . 'parents and the Board on any working day.</p><table>'
            . implode('', array_map(fn ($k, $v) => '<tr><th style="width:38%">' . e($k) . '</th><td>' . e((string) $v ?: '—') . '</td></tr>', array_keys($rows), $rows))
            . '</table>';
    }

    private function feeTable(SchoolSiteData $lms): string
    {
        $groups = $lms->fees();
        if (! $groups) {
            $groups = array_map(fn ($c) => ['class' => $c[0], 'section' => null, 'rows' => [
                ['name' => 'Tuition Fee (monthly)', 'amount' => $c[1]], ['name' => 'Annual Charges', 'amount' => $c[2]], ['name' => 'Exam Fee (yearly)', 'amount' => 1500],
            ], 'total' => $c[1] + $c[2] + 1500], [['Nursery – UKG', 2200, 6000], ['I – V', 2800, 7500], ['VI – VIII', 3300, 8500], ['IX – X', 3900, 9500], ['XI – XII', 4600, 11000]]);
        }

        $html = '<table><tr><th style="width:24%">Class</th><th>Particulars</th><th class="r" style="width:22%">Amount</th></tr>';
        foreach ($groups as $g) {
            $label = $g['class'] . ($g['section'] ? ' (' . $g['section'] . ')' : '');
            foreach ($g['rows'] as $j => $r) {
                $html .= '<tr>' . ($j === 0 ? '<td rowspan="' . (count($g['rows']) + 1) . '"><b>' . e($label) . '</b></td>' : '')
                    . '<td>' . e($r['name']) . '</td><td class="r">' . e(SchoolSiteData::money((float) $r['amount'])) . '</td></tr>';
            }
            $html .= '<tr><td><b>Total</b></td><td class="r"><b>' . e(SchoolSiteData::money((float) $g['total'])) . '</b></td></tr>';
        }

        return $html . '</table>';
    }

    private function feeSchedule(): string
    {
        $rows = [['Quarter 1 (April – June)', 'By 10 April'], ['Quarter 2 (July – September)', 'By 10 July'],
            ['Quarter 3 (October – December)', 'By 10 October'], ['Quarter 4 (January – March)', 'By 10 January']];

        return '<table><tr><th>Instalment</th><th style="width:30%">Due date</th></tr>'
            . implode('', array_map(fn ($r) => '<tr><td>' . e($r[0]) . '</td><td>' . e($r[1]) . '</td></tr>', $rows)) . '</table>'
            . '<p style="margin-top:14px"><b>Late fee:</b> ₹10 a day after the due date.</p>'
            . '<p><b>Concessions:</b> 10% on the tuition fee for the second child of the same parents; '
            . 'full tuition fee waiver for children of the school\'s teaching staff; merit scholarships for students scoring above 95% in Class X.</p>'
            . '<p><b>Payment:</b> online through the school app, by UPI, or at the school counter between 8:00 AM and 1:00 PM.</p>';
    }

    private function eventTable(SchoolSiteData $lms, ?string $type): string
    {
        $events = array_values(array_filter($lms->events(), fn ($e) => ! $type || $e['type'] === $type));
        if (! $events) {
            return '<p>The calendar will be published at the start of the session.</p>';
        }

        return '<table><tr><th style="width:22%">Date</th><th>Event</th><th style="width:16%">Type</th></tr>'
            . implode('', array_map(fn ($e) => '<tr><td>' . e(\Carbon\Carbon::parse($e['date'])->format('D, j M Y')) . '</td><td><b>' . e($e['title']) . '</b>'
                . ($e['description'] ? '<br><span style="color:#666">' . e($e['description']) . '</span>' : '') . '</td><td>' . e($e['type']) . '</td></tr>', $events))
            . '</table>';
    }

    private function resultTable(array $results): string
    {
        return '<table><tr><th>Year</th><th class="r">Appeared</th><th class="r">Passed</th><th class="r">Pass %</th></tr>'
            . implode('', array_map(fn ($r) => '<tr><td>' . e($r['year']) . '</td><td class="r">' . e($r['appeared']) . '</td><td class="r">' . e($r['passed'])
                . '</td><td class="r">' . ((int) $r['appeared'] ? number_format($r['passed'] / $r['appeared'] * 100, 1) : '—') . '</td></tr>', $results))
            . '</table>';
    }

    private function blank(mixed $v): bool
    {
        return $v === null || $v === [] || (is_string($v) && trim($v) === '');
    }
}
