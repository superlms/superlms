<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Drawer\Utils;
use Livewire\Form;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use Livewire\Mechanisms\HandleComponents\HandleComponents;

use function Livewire\on;
use function Livewire\store;

/**
 * A refresh keeps the page where it was (user's ask of 2026-11-25): in the
 * admin, Super Admin and accounts panels, reloading a page — F5, the reload
 * button or Ctrl+Shift+R — brings back the open tab, the filters, the student
 * picked, the slide-in panel and what was typed in it, instead of the page's
 * first screen. One hook for every Livewire component; no page is touched.
 *
 * How:
 *  - After every Livewire request a component's public state is kept in the
 *    cache (Redis) for this browser session, two hours, under a slot for the
 *    page's path + the component's name + its key on the page (so two pages,
 *    or two schools, never mix). Each slot is its own small cache entry: the
 *    session itself does not grow.
 *  - Opening a page any other way (a link, the sidebar, wire:navigate, typing
 *    a new address) starts it fresh and drops what was kept for it.
 *  - On a reload of the same page (the browser sends Cache-Control: max-age=0
 *    or no-cache) the state is put back right after the component's mount():
 *      1. every plain value (a tab, a filter, an id, a flag, a typed field) one
 *         by one in the order the component declares them, through Livewire's
 *         own update path — so each one's updated…() hook runs as when it was
 *         picked, and lists and figures it loads come fresh from the database;
 *      2. then each plain value exactly as it was, in case a later hook
 *         cleared one (picking a class clears the section);
 *      3. then the rest — lists, arrays, form objects, models — as kept, but
 *         only where neither mount() nor a hook above filled it afresh.
 *    Nothing is restored on a page that is not signed in, nor passwords, codes
 *    or uploads; a value that no longer fits (a deleted record) is skipped.
 */
class KeepStateOnReload
{
    private const CACHE    = 'lms_keep:';
    private const MEMO     = 'lmsKeep';
    private const MAX_SLOT = 200_000;   // bytes of one component's kept state
    private const KEEP_FOR = 7200;      // seconds (the panel's session lifetime)

    /** Never kept: secrets and what only means something once. */
    private const SECRET = '/pass(word)?|otp|pin$|secret|token|captcha|cvv/i';

    /** Components that start fresh on every load (the Ask AI pop-up, sign-in screens). */
    private const SKIP = '/gemini|login|app-lock|forgot|reset-password|otp/i';

    public static function register(): void
    {
        on('mount', function ($component, $params, $key, $parent) {
            try {
                self::mounted($component, $key, $parent);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        on('hydrate', function ($component, $memo) {
            if (isset($memo[self::MEMO]) && is_array($memo[self::MEMO])) {
                store($component)->set(self::MEMO, $memo[self::MEMO]);
            }
        });

        on('dehydrate', function ($component, $context) {
            try {
                self::dehydrated($component, $context);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    // ─── The hooks ───────────────────────────────────────────────────────────

    private static function mounted(Component $component, $key, $parent): void
    {
        if (! self::applies($component)) {
            return;
        }

        // The page this component is on: the request's own path on a page load;
        // for one that appears during a Livewire request, its parent's page.
        $path = app('livewire')->isLivewireRequest()
            ? ($parent ? (store($parent)->get(self::MEMO)['p'] ?? null) : null)
            : '/' . ltrim(request()->path(), '/');
        if ($path === null) {
            return;
        }

        $slot = self::slot($path, $component->getName(), $key);
        store($component)->set(self::MEMO, ['p' => $path, 's' => $slot]);

        if (self::isReload()) {
            $kept = cache()->get(self::cacheKey($slot));
            if (is_string($kept) && is_array($data = json_decode($kept, true))) {
                self::restore($component, $data);
            }
        } else {
            cache()->forget(self::cacheKey($slot));
        }
    }

    private static function dehydrated(Component $component, ComponentContext $context): void
    {
        $mark = store($component)->get(self::MEMO);
        if (! is_array($mark) || empty($mark['s'])) {
            return;
        }

        // Carried in the snapshot so the next request knows the slot again.
        $context->addMemo(self::MEMO, $mark);

        // A first render is the page's own starting state: nothing to keep.
        if ($context->isMounting()) {
            return;
        }

        $data = self::dehydrateState($component);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false || strlen($json) > self::MAX_SLOT) {
            cache()->forget(self::cacheKey($mark['s']));
            return;
        }

        cache()->put(self::cacheKey($mark['s']), $json, self::KEEP_FOR);
    }

    /** What is kept for a component on a page in this session (null: nothing). */
    public static function kept(string $path, string $name, ?string $key = null): ?array
    {
        $json = cache()->get(self::cacheKey(self::slot('/' . ltrim($path, '/'), $name, $key)));

        return is_string($json) ? json_decode($json, true) : null;
    }

    private static function slot(string $path, string $name, $key): string
    {
        return sha1($path . '|' . $name . '|' . ($key ?? ''));
    }

    private static function cacheKey(string $slot): string
    {
        return self::CACHE . sha1(session()->getId()) . ':' . $slot;
    }

    // ─── Restoring ───────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data the component's public state as kept (Livewire's dehydrated form) */
    public static function restore(Component $component, array $data): void
    {
        $handler = app(HandleComponents::class);
        $context = new ComponentContext($component, mounting: true);
        $mounted = Utils::getPublicPropertiesDefinedOnSubclass($component);

        // The kept values, back as PHP values (a model is read afresh from the
        // database; one that is gone is skipped).
        $values = [];
        foreach ($data as $name => $raw) {
            if (! array_key_exists($name, $mounted) || preg_match(self::SECRET, $name) || self::isUpload($raw)) {
                continue;
            }
            try {
                $values[$name] = (fn () => $this->hydrate($raw, $context, $name))->call($handler);
            } catch (\Throwable $e) {
                continue;
            }
        }

        // 1. Plain values one by one, through Livewire's update path (hooks run).
        foreach ($values as $name => $value) {
            if (! self::plain($value) || self::same($component->$name ?? null, $value)) {
                continue;
            }
            try {
                $finish = $handler->updateProperty($component, $name, $value, $context);
                $finish();
            } catch (\Throwable $e) {
                // Locked, or its hook could not run now — step 2 still sets it.
            }
        }

        // 2. Each plain value exactly as it was.
        foreach ($values as $name => $value) {
            if (self::plain($value) && ! self::same($component->$name ?? null, $value)) {
                self::set($component, $name, $value);
            }
        }

        // 3. The rest, where nothing filled it afresh: mount() left it at its
        //    declared default (or it has none, as a form object) and no hook
        //    above replaced it.
        foreach ($values as $name => $value) {
            if (self::plain($value)) {
                continue;
            }
            $now    = $component->$name ?? null;
            $before = $mounted[$name] ?? null;
            $untouched = is_object($before) ? $now === $before : $now == $before;
            if ($untouched && (self::atDefault($component, $name, $before) || $before instanceof Form)) {
                self::set($component, $name, $value);
            }
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static function applies(Component $component): bool
    {
        if (preg_match(self::SKIP, $component->getName())) {
            return false;
        }
        foreach (['admin', 'superadmin', 'accounts'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }

    /** A page load the browser made by reloading (F5, the button, Ctrl+Shift+R). */
    public static function isReload(): bool
    {
        $request = request();
        if (! $request->isMethod('GET') || app('livewire')->isLivewireRequest() || $request->hasHeader('X-Livewire-Navigate')) {
            return false;
        }
        $cache  = strtolower((string) $request->header('Cache-Control', ''));
        $pragma = strtolower((string) $request->header('Pragma', ''));

        return str_contains($cache, 'max-age=0') || str_contains($cache, 'no-cache') || str_contains($pragma, 'no-cache');
    }

    /** @return array<string, mixed> */
    private static function dehydrateState(Component $component): array
    {
        $handler = app(HandleComponents::class);
        $context = new ComponentContext($component);
        $data    = (fn () => $this->dehydrateProperties($component, $context))->call($handler);

        return array_filter($data, fn ($raw, $name) => ! preg_match(self::SECRET, (string) $name) && ! self::isUpload($raw),
            ARRAY_FILTER_USE_BOTH);
    }

    private static function isUpload($raw): bool
    {
        return str_contains((string) json_encode($raw), '"s":"fil"');
    }

    private static function plain($value): bool
    {
        return $value === null || is_scalar($value) || $value instanceof \BackedEnum;
    }

    private static function same($a, $b): bool
    {
        if ($a === $b) {
            return true;
        }
        // '5' from a select and 5 from the database are the same pick.
        return (is_scalar($a) || $a === null) && (is_scalar($b) || $b === null)
            && ! is_bool($a) && ! is_bool($b) && $a !== null && $b !== null
            && (string) $a === (string) $b;
    }

    private static function atDefault(Component $component, string $name, $value): bool
    {
        try {
            $prop = new \ReflectionProperty($component, $name);
        } catch (\ReflectionException $e) {
            return false;
        }
        if (! $prop->hasDefaultValue()) {
            return $value === null;
        }

        return $prop->getDefaultValue() == $value;
    }

    private static function set(Component $component, string $name, $value): void
    {
        try {
            $component->$name = $value;
        } catch (\Throwable $e) {
            // A value that no longer fits the property's type: left as mounted.
        }
    }
}
