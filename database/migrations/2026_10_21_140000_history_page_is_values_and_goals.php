<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * About Us → History is now Values & Goals, read from the School Profile
     * (user's ask of 2026-10-21): a site that had History switched on gets
     * Values & Goals in its place. Sites with every page on (no list) need
     * nothing.
     */
    public function up(): void
    {
        $this->swap('history', 'values-goals');
    }

    public function down(): void
    {
        $this->swap('values-goals', 'history');
    }

    private function swap(string $from, string $to): void
    {
        DB::table('school_websites')->whereNotNull('pages')->orderBy('id')->get(['id', 'pages'])
            ->each(function ($row) use ($from, $to) {
                $pages = json_decode((string) $row->pages, true);
                if (! is_array($pages) || ! in_array($from, $pages, true)) {
                    return;
                }
                $pages = array_values(array_unique(array_map(fn ($p) => $p === $from ? $to : $p, $pages)));
                DB::table('school_websites')->where('id', $row->id)->update(['pages' => json_encode($pages)]);
            });
    }
};
