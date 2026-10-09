<?php

namespace Tests\Feature;

use App\Livewire\Admin\Syllabus;
use Tests\TestCase;

/**
 * Manage Chapters: taking a chapter off closes the numbers up behind it, and a
 * saved one taken off is still deleted on save.
 */
class SyllabusChapterNumbersTest extends TestCase
{
    private function rows(array $orders): Syllabus
    {
        $page = new Syllabus();
        $page->chapterRows = array_map(fn ($o, $i) => ['id' => $i + 100, 'name' => "CHAPTER $o", 'order' => $o], $orders, array_keys($orders));

        return $page;
    }

    private function orders(Syllabus $page): array
    {
        return array_column($page->chapterRows, 'order');
    }

    public function test_numbers_after_the_one_taken_off_move_down(): void
    {
        $page = $this->rows([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $page->removeChapterRow(5);                                   // chapter 6

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], $this->orders($page));
        $this->assertSame(['CHAPTER 7', 'CHAPTER 8'], [$page->chapterRows[5]['name'], $page->chapterRows[6]['name']]); // names untouched
        $this->assertContains(105, $page->deletedChapterIds);
    }

    public function test_last_row_and_a_new_row_typed_as_text(): void
    {
        $page = $this->rows([1, 2, 3]);
        $page->removeChapterRow(2);
        $this->assertSame([1, 2], $this->orders($page));

        $page->chapterRows[] = ['id' => null, 'name' => 'New', 'order' => '4'];
        $page->removeChapterRow(0);
        $this->assertSame([1, 3], $this->orders($page));               // "4" (text) → 3 as well
    }

    public function test_no_shift_when_the_number_is_still_held_or_blank(): void
    {
        $page = $this->rows([1, 2, 2, 3]);
        $page->removeChapterRow(1);                                    // another row is still 2
        $this->assertSame([1, 2, 3], $this->orders($page));

        $page = $this->rows([1, '', 3]);
        $page->removeChapterRow(1);                                    // no number to close up
        $this->assertSame([1, 3], $this->orders($page));
    }
}
