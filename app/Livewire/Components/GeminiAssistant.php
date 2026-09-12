<?php

namespace App\Livewire\Components;

use App\Services\Gemini\GeminiAssistant as Assistant;
use App\Services\Gemini\GeminiException;
use App\Services\Gemini\LmsScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The floating assistant mounted on every panel page.
 *
 * Asking is two round-trips on purpose: `submit()` paints the user's bubble and
 * the typing dots immediately, then the browser calls `run()`, which is the one
 * that waits on Gemini. One trip would leave the panel frozen with nothing on
 * screen for as long as the model takes.
 */
class GeminiAssistant extends Component
{
    public bool $open = false;

    public string $prompt = '';

    /** @var array<int,array{role:string,text:string}> */
    public array $messages = [];

    /** Set between submit() and run() so the view can show the typing state. */
    #[Locked]
    public bool $pending = false;

    #[Locked]
    public string $scopeLabel = '';

    /** @var array<int,string> */
    #[Locked]
    public array $suggestions = [];

    public function mount(): void
    {
        $scope = ($user = Auth::user()) ? LmsScope::for($user) : null;

        if (! $scope) {
            return;
        }

        $this->scopeLabel  = $scope->isSchool() ? 'This school' : 'All schools';
        $this->suggestions = $scope->isSchool()
            ? [
                'How many students do we have, class-wise?',
                'How much fee was collected this month?',
                'Who has pending fees?',
                'Aaj ki attendance kya hai?',
            ]
            : [
                'How many schools are active?',
                'Platform fees collected this month?',
                'Show the newest schools',
                'Pending credit requests kaunse hain?',
            ];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function clear(): void
    {
        $this->messages = [];
        $this->pending  = false;
        $this->prompt   = '';
    }

    public function useSuggestion(int $index): void
    {
        $this->prompt = $this->suggestions[$index] ?? '';
        $this->submit();
    }

    public function submit(): void
    {
        $question = trim($this->prompt);

        if ($question === '' || $this->pending) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => Str::limit($question, 1000, '')];
        $this->prompt     = '';
        $this->pending    = true;

        // Keep the transcript from growing without bound in a long session.
        if (count($this->messages) > 40) {
            $this->messages = array_slice($this->messages, -40);
        }

        $this->dispatch('gemini-run');
    }

    public function run(Assistant $assistant): void
    {
        if (! $this->pending) {
            return;
        }

        $this->pending = false;

        $user = Auth::user();
        $last = end($this->messages) ?: null;

        if (! $user || ! $last || $last['role'] !== 'user') {
            return;
        }

        if (! $assistant->available()) {
            $this->messages[] = ['role' => 'model', 'text' => 'The assistant is not configured yet. An administrator needs to set GEMINI_API_KEY.'];

            return;
        }

        // Everything before the question just asked is the conversation so far.
        $history = array_slice($this->messages, 0, -1);

        try {
            $answer = $assistant->ask($user, $last['text'], $history);
            $this->messages[] = ['role' => 'model', 'text' => $answer['text']];
        } catch (GeminiException $e) {
            $this->messages[] = ['role' => 'model', 'text' => $e->userMessage()];
        } catch (\Throwable $e) {
            Log::error('gemini.assistant failed', ['error' => $e->getMessage()]);
            $this->messages[] = ['role' => 'model', 'text' => 'Something went wrong while answering. Please try again.'];
        }
    }

    /** Model output is markdown; render it with raw HTML stripped out. */
    public function html(string $text): string
    {
        return Str::markdown($text, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render()
    {
        $enabled = Auth::check()
            && in_array(Auth::user()->role, (array) config('gemini.roles', []), true)
            && app(Assistant::class)->available();

        return view('livewire.components.gemini-assistant', ['enabled' => $enabled]);
    }
}
