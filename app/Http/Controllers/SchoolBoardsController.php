<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use App\Support\BoardsStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Boards page of a school's website (user's ask of 2026-11-25): a Class
 * 9–12 student signs in with their name, the first word of their father's
 * name and their date of birth, and gets their details, fee and the school's
 * payment QR (App\Support\BoardsStudent). Not one of the three wrong, and
 * nothing is shown.
 *
 *   POST /api/website/school-boards/login  {organization_id, name, father, dob}
 *   POST /api/website/school-boards/me     {organization_id, token}
 *
 * Both answer with the page's student part as HTML and a token the tab keeps
 * (sessionStorage) to stay signed in for a while.
 */
class SchoolBoardsController extends Controller
{
    public const NO_MATCH = 'These details do not match a Class 9 to 12 student of the school. Check the spelling of both names and the date of birth.';

    public function login(Request $request): JsonResponse
    {
        $v = $request->validate([
            'organization_id' => 'required|integer',
            'name'            => 'required|string|max:100',
            'father'          => 'required|string|max:100',
            'dob'             => 'required|date_format:Y-m-d',
        ], [
            'name.required'   => 'Please enter your name.',
            'father.required' => "Please enter the first word of your father's name.",
            'dob.required'    => 'Please enter your date of birth.',
            'dob.date_format' => 'Please enter your date of birth.',
        ]);

        if (! $this->boardsOn((int) $v['organization_id'])) {
            return response()->json(['success' => false, 'message' => 'Boards login is not available on this website.'], 404);
        }

        $boards  = new BoardsStudent((int) $v['organization_id']);
        $student = $boards->find($v['name'], $v['father'], $v['dob']);
        if (! $student) {
            return response()->json(['success' => false, 'message' => self::NO_MATCH], 422);
        }

        return $this->signedIn($boards, $student);
    }

    public function me(Request $request): JsonResponse
    {
        $v = $request->validate([
            'organization_id' => 'required|integer',
            'token'           => 'required|string|max:2000',
        ]);

        $boards  = new BoardsStudent((int) $v['organization_id']);
        $student = $this->boardsOn((int) $v['organization_id']) ? $boards->fromToken($v['token']) : null;
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
        }

        return $this->signedIn($boards, $student);
    }

    private function signedIn(BoardsStudent $boards, $student): JsonResponse
    {
        return response()->json([
            'success' => true,
            'token'   => $boards->token($student),
            'html'    => view('school-site.shared.boards-result', ['b' => $boards->data($student)])->render(),
        ]);
    }

    /** The school has a published website with its Boards page on. */
    private function boardsOn(int $orgId): bool
    {
        $site = SchoolWebsite::where('organization_id', $orgId)->where('status', true)->first();

        return $site !== null && $site->isPageEnabled('boards');
    }
}
