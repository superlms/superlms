<?php

namespace App\Http\Controllers\v1;

use App\Models\User;
use App\Services\Gemini\GeminiAssistant;
use App\Services\Gemini\GeminiException;
use App\Services\Gemini\GeminiQuota;
use App\Services\Gemini\LmsScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The Super LMS assistant (LMS Assist) for the mobile app — the same assistant
 * as the web panel's floating one (App\Livewire\Components\GeminiAssistant):
 * read-only answers about the signed-in user's own school, held to that
 * login's screen permissions, with the daily question allowance its role
 * shares on the web.
 *
 *   GET  /assistant       { enabled, name, scope, suggestions, unlimited, daily_limit, remaining, resets }
 *   POST /assistant/ask   { question, history: [{ role: user|model, text }] } → { text, remaining }
 *
 * The app keeps the conversation and sends it back with each question; the
 * answer is markdown.
 */
class AssistantController extends ApiController
{
    /** GET /api/v1/assistant */
    public function status(GeminiAssistant $assistant)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $scope = $this->scopeFor($user);
        if (!$scope) {
            return $this->error('The assistant is not available for this account.', 403);
        }

        return $this->success(array_merge([
            'enabled'     => $assistant->available(),
            'name'        => config('gemini.brand', 'Super LMS'),
            'scope'       => $scope->isSchool() ? 'This school' : 'All schools',
            'suggestions' => GeminiAssistant::suggestionsFor($scope),
        ], $this->quota($scope)), 'Assistant fetched.');
    }

    /** POST /api/v1/assistant/ask */
    public function ask(Request $request, GeminiAssistant $assistant)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $scope = $this->scopeFor($user);
        if (!$scope) {
            return $this->error('The assistant is not available for this account.', 403);
        }

        if ($err = $this->validateWith($request, [
            'question'       => 'required|string|max:1000',
            'history'        => 'nullable|array|max:40',
            'history.*.role' => 'required|in:user,model',
            'history.*.text' => 'required|string|max:8000',
        ])) return $err;

        if (!$assistant->available()) {
            return $this->error('The assistant is not configured yet. An administrator needs to set its API key in the environment.', 503);
        }

        $history = array_map(
            fn ($turn) => ['role' => $turn['role'], 'text' => (string) $turn['text']],
            array_values((array) $request->input('history', []))
        );

        try {
            $answer = $assistant->ask($user, trim((string) $request->input('question')), $history);
        } catch (GeminiException $e) {
            return $this->error(
                $e->userMessage(),
                in_array($e->status, [403, 429], true) ? $e->status : 503,
                $this->quota($scope)
            );
        } catch (\Throwable $e) {
            Log::error('gemini.assistant (app) failed', ['error' => $e->getMessage()]);

            return $this->error('Something went wrong while answering. Please try again.', 500);
        }

        return $this->success(array_merge(
            ['text' => $answer['text']],
            $this->quota($scope)
        ), 'Answered.');
    }

    /** The web assistant's roles only. */
    private function scopeFor(User $user): ?LmsScope
    {
        return in_array($user->role, (array) config('gemini.roles', []), true)
            ? LmsScope::for($user)
            : null;
    }

    /** The day's allowance, shared by everyone with this role in this school. */
    private function quota(LmsScope $scope): array
    {
        $quota     = new GeminiQuota($scope);
        $unlimited = $quota->unlimited();

        return [
            'unlimited'   => $unlimited,
            'daily_limit' => $unlimited ? null : $quota->limit(),
            'remaining'   => $unlimited ? null : $quota->remaining(),
            'resets'      => $quota->resetDescription(),
        ];
    }
}
