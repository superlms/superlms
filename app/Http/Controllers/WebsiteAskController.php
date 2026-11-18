<?php

namespace App\Http\Controllers;

use App\Services\Gemini\GeminiException;
use App\Services\Gemini\WebsiteAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/website/ask — the "Ask anything" pill on superlms.in.
 * { question, history: [{role: user|model, text}] } → { success, answer }.
 * On failure { success: false, message } — a sentence fit for the chat, the
 * page then offers WhatsApp with the same question.
 */
class WebsiteAskController extends Controller
{
    public function __invoke(Request $request, WebsiteAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'question'       => ['required', 'string', 'min:2', 'max:500'],
            'history'        => ['nullable', 'array', 'max:16'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model'],
            'history.*.text' => ['required_with:history', 'string', 'max:3000'],
        ], [
            'question.required' => 'Type a question first.',
            'question.max'      => 'Please keep the question under 500 characters.',
        ]);

        if (!$assistant->available()) {
            return response()->json([
                'success' => false,
                'message' => 'The assistant is resting right now. Call or WhatsApp the SUPERLMS team on +91 9084748563 — they will be glad to help.',
            ], 503);
        }

        try {
            $answer = $assistant->answer(
                trim($data['question']),
                $data['history'] ?? [],
                (string) $request->ip(),
            );
        } catch (GeminiException $e) {
            return response()->json([
                'success' => false,
                'message' => ($e->status === 429 && $e->friendly !== null)
                    ? $e->friendly
                    : 'I could not get an answer just now. Please try again in a moment, or WhatsApp the team on +91 9084748563.',
            ], $e->status === 429 ? 429 : 503);
        }

        return response()->json(['success' => true, 'answer' => $answer]);
    }
}
