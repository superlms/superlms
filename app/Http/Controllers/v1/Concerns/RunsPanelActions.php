<?php

namespace App\Http\Controllers\v1\Concerns;

use Illuminate\Validation\ValidationException;
use Livewire\Component;

use function Livewire\store;

/**
 * Running one of an admin panel component's actions over the API, the way its
 * button runs it, and answering the way its toast does.
 *
 * The component is made with `new`, its properties set as the panel's form
 * would hold them, and the action called. What stopped it — a validation
 * message, an error added to a field, an error toast — comes back as a 422
 * with the field named the app's way; otherwise the success toast's words are
 * the message. Used by the admin app's Fees and Payroll controllers, so the
 * app saves exactly what the panel saves.
 *
 * The host provides success() and error() (ApiController).
 */
trait RunsPanelActions
{
    /** Call a (public, protected or private) method of the component. */
    protected function callOn(Component $page, string $method, ...$args)
    {
        return (fn () => $this->{$method}(...$args))->call($page);
    }

    /**
     * Run the action and answer as its toast does. $fields renames the
     * component's properties to the app's field names; a key ending in '.' (or
     * in '.s' for the panel's "s{id}" keys) renames everything under it.
     */
    protected function act(Component $page, string $method, array $args = [], array $fields = [], string $fallback = 'Saved.', ?callable $data = null)
    {
        try {
            $this->callOn($page, $method, ...$args);
        } catch (ValidationException $e) {
            return $this->fieldError($e->errors(), $fields);
        }

        $bag = $page->getErrorBag();
        if ($bag->isNotEmpty()) {
            return $this->fieldError($bag->toArray(), $fields);
        }

        [$ok, $bad] = $this->toasts($page);
        if ($bad !== null) {
            return $this->error($bad, 422);
        }

        return $this->success($data ? $data($page) : null, $ok ?? $fallback);
    }

    /** The first success (or info) toast and the first error toast the component sent, each as one line. */
    protected function toasts(Component $page): array
    {
        $ok = $bad = null;

        foreach ((array) store($page)->get('dispatched', []) as $event) {
            $e = is_object($event) && method_exists($event, 'serialize') ? $event->serialize() : [];
            if (($e['name'] ?? '') !== 'wireui:notification') {
                continue;
            }
            $o     = $e['params'][0]['options'] ?? ($e['params']['options'] ?? []);
            $title = trim((string) ($o['title'] ?? ''));
            $desc  = trim((string) ($o['description'] ?? ''));
            $line  = $desc === ''
                ? $title
                : ($title === '' ? $desc : $title . (preg_match('/[.!?]$/u', $title) ? ' ' : ' — ') . $desc);

            if (($o['icon'] ?? '') === 'error') {
                $bad ??= $line;
            } elseif (in_array($o['icon'] ?? '', ['success', 'info'], true)) {
                $ok ??= $line;
            }
        }

        return [$ok, $bad];
    }

    /** A 422 naming the first wrong field, with every field's messages keyed the app's way. */
    protected function fieldError(array $errors, array $fields)
    {
        $out = [];
        foreach ($errors as $key => $messages) {
            $out[$this->renameField((string) $key, $fields)] = array_values((array) $messages);
        }
        $first = collect($out)->flatten()->first() ?: 'Please check the form.';

        return $this->error($first, 422, $out);
    }

    private function renameField(string $key, array $fields): string
    {
        if (isset($fields[$key])) {
            return $fields[$key];
        }
        foreach ($fields as $from => $to) {
            if ((str_ends_with($from, '.') || str_ends_with($from, '.s')) && str_starts_with($key, $from)) {
                return $to . substr($key, strlen($from));
            }
        }
        return $key;
    }
}
