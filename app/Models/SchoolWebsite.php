<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A school's public marketing website (Kider template), served on its own
 * custom domain. Content is auto-fetched from the Organization / SchoolInfo
 * where available, and any field can be overridden from the super-admin
 * builder (stored in the `content` JSON column).
 */
class SchoolWebsite extends Model
{
    protected $fillable = [
        'organization_id',
        'domain',
        'template',
        'theme',
        'pages',
        'content',
        'status',
    ];

    protected $casts = [
        'theme'   => 'array',
        'pages'   => 'array',
        'content' => 'array',
        'status'  => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // ─── Catalogue: pages & themes ─────────────────────────────────────────

    /** All template pages that can be toggled on/off ('home' is mandatory). */
    public static function allPages(): array
    {
        return [
            'home'        => 'Home',
            'about'       => 'About Us',
            'leadership'  => 'Leadership',
            'facilities'  => 'Facilities',
            'classes'     => 'Classes',
            'admission'   => 'Admissions',
            'gallery'     => 'Gallery',
            'team'        => 'Our Team',
            'appointment' => 'Appointment',
            'contact'     => 'Contact',
        ];
    }

    /** Built-in colour presets (primary / light tint / dark). */
    public static function themePresets(): array
    {
        return [
            'sunset' => ['label' => 'Sunset (default)', 'primary' => '#FE5D37', 'light' => '#FFF5F3', 'dark' => '#103741'],
            'ocean'  => ['label' => 'Ocean Blue',       'primary' => '#2C7BE5', 'light' => '#EDF4FF', 'dark' => '#0B2545'],
            'forest' => ['label' => 'Forest Green',     'primary' => '#1E9E62', 'light' => '#ECFBF3', 'dark' => '#10362A'],
            'royal'  => ['label' => 'Royal Purple',     'primary' => '#6F56FE', 'light' => '#F2EFFF', 'dark' => '#1A0F2E'],
            'rose'   => ['label' => 'Rose Pink',        'primary' => '#E6477F', 'light' => '#FDEEF4', 'dark' => '#2E1020'],
            'amber'  => ['label' => 'Amber Gold',       'primary' => '#E8A009', 'light' => '#FFF8E8', 'dark' => '#3A2A05'],
        ];
    }

    /** The resolved theme (preset values, with optional primary override). */
    public function resolvedTheme(): array
    {
        $presets = static::themePresets();
        $theme   = $this->theme ?? [];
        $preset  = $presets[$theme['preset'] ?? 'sunset'] ?? $presets['sunset'];

        return [
            'primary' => ($theme['primary'] ?? null) ?: $preset['primary'],
            'light'   => ($theme['light']   ?? null) ?: $preset['light'],
            'dark'    => ($theme['dark']    ?? null) ?: $preset['dark'],
        ];
    }

    /** Enabled page slugs (home always included). */
    public function enabledPages(): array
    {
        $pages = $this->pages ?: array_keys(static::allPages());
        return array_values(array_unique(array_merge(['home'], $pages)));
    }

    public function isPageEnabled(string $slug): bool
    {
        return in_array($slug, $this->enabledPages(), true);
    }

    // ─── Content auto-fetch + resolution ───────────────────────────────────

    /**
     * Default content auto-fetched from the Organization / SchoolInfo.
     * These act as fallbacks when the builder has not overridden a field.
     */
    public function autoContent(): array
    {
        $org    = $this->organization;
        $info   = $org?->schoolInfo;
        $name   = $org?->name ?: 'Our School';

        $team = [];
        if ($info && $info->relationLoaded('managementTeam') === false) {
            $info->loadMissing('managementTeam');
        }
        foreach (($info?->managementTeam ?? []) as $m) {
            $team[] = [
                'name'  => $m->name ?? '',
                'role'  => $m->designation ?? '',
                'photo' => $m->photo_path ?? '',
            ];
        }

        return [
            'school_name'   => $name,
            'tagline'       => $org?->education_board ? ($org->education_board . ' School') : 'Quality Education',
            'motto'         => '',
            'medium'        => '',
            'board'         => $org?->education_board ?? '',
            'affiliation_no'=> '',
            'school_code'   => '',
            'logo'          => $org?->logo ?? '',

            'hero_title'    => 'Welcome to ' . $name,
            'hero_subtitle' => 'Nurturing young minds with care, creativity and quality education for a brighter future.',

            'about_heading' => 'Learn More About ' . $name,
            'about_text'    => $info?->about_school ?: 'We are committed to providing a safe, joyful and inspiring environment where every child can learn, grow and thrive.',
            'about_text2'   => '',
            'history_text'  => '',
            'philosophy'    => '',
            'vision'        => $info?->usm_vision ?? '',
            'mission'       => $info?->usm_mission ?? '',

            // Admissions
            'admission_intro'   => 'Admissions are open. Join our school family — enquire today and give your child the best start.',
            'admission_session' => '',
            'fee_note'          => '',
            'curriculum_text'   => '',

            'cta_heading'   => 'Admissions Open',
            'cta_text'      => 'Give your child the best start. Enquire today and become part of the ' . $name . ' family.',

            'phone'         => $org?->mobile_number ?: ($info?->school_mobile ?? ''),
            'email'         => $org?->email ?: ($info?->school_email ?? ''),
            'address'       => $org?->address ?: ($info?->school_address ?? ''),

            'facebook'      => '',
            'instagram'     => '',
            'youtube'       => '',
            'twitter'       => '',
            'telegram'      => '',

            // Repeatable lists (empty → template shows sensible samples)
            'classes'           => [],
            'team'              => $team,
            'leadership'        => [],   // [{name, role, photo, message}]
            'facilities'        => [],   // [{icon, title, desc}]
            'why_us'            => [],   // [{icon, title, desc}]
            'stats'             => [],   // [{value, label}]
            'admission_steps'   => [],   // [{title, desc}]
            'documents_required'=> [],   // [{text}]
            'admission_rules'   => [],   // [{text}]
            'gallery'           => [],   // [{image, caption}]
        ];
    }

    /** Stored overrides merged over the auto-fetched content. */
    public function resolvedContent(): array
    {
        $auto    = $this->autoContent();
        $stored  = $this->content ?? [];

        // Scalars: stored value wins when non-empty; lists: stored wins when non-empty.
        $out = $auto;
        foreach ($stored as $key => $val) {
            if (is_array($val)) {
                if (!empty($val)) {
                    $out[$key] = $val;
                }
            } elseif ($val !== null && $val !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }

    /**
     * Resolve a stored media value to a usable URL.
     * Absolute URLs are returned as-is; relative paths go through asset();
     * empty values fall back to the given template asset.
     */
    public static function media(?string $value, ?string $fallback = null): ?string
    {
        if (! $value) {
            return $fallback ? asset('school-templates/kider/img/' . $fallback) : null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return asset(ltrim($value, '/'));
    }

    /** URL of the school's admin panel login on the main app. */
    public function adminLoginUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/login';
    }

    /** Resolve a published website for an incoming host, or null. */
    public static function forHost(?string $host): ?self
    {
        if (! $host) {
            return null;
        }
        $host = preg_replace('/:\d+$/', '', strtolower($host)); // strip port

        return static::with('organization.schoolInfo')
            ->where('status', true)
            ->where(function ($q) use ($host) {
                $q->where('domain', $host)
                  ->orWhere('domain', 'www.' . $host)
                  ->orWhere('domain', preg_replace('/^www\./', '', $host));
            })
            ->first();
    }
}
