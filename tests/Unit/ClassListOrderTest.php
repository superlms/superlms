<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every class list and class picker (admin, accounts, super admin and the
 * app's API) lists classes the way the Standard page does — its Order, then
 * as added — through Standard::inClassOrder(), never by id or by name.
 */
class ClassListOrderTest extends TestCase
{
    public function test_no_class_list_is_ordered_by_id_or_name(): void
    {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        foreach (['app/Livewire', 'app/Http/Controllers'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$root}/{$dir}"));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $code = file_get_contents($file->getPathname());
                // A Standard query up to the call that fetches it.
                preg_match_all('/(?<![A-Za-z])Standard::(?:where|query|with)\b.*?->(?:get|pluck|paginate|first|count|exists|max|value|find|findOrFail)\(/s', $code, $m);
                foreach ($m[0] as $chain) {
                    $chain = str_replace("->orderBy('order')->orderBy('id')", '', preg_replace('/\s+/', '', $chain));
                    if (preg_match("/->orderBy\\('(id|name)'\\)/", $chain)) {
                        $offenders[] = substr($file->getPathname(), strlen($root) + 1) . ': ' . preg_replace('/\s+/', ' ', substr($chain, 0, 120));
                    }
                }
            }
        }

        $this->assertSame([], $offenders);
    }
}
