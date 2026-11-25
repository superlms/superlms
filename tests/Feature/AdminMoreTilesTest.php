<?php

namespace Tests\Feature;

use App\Livewire\Admin\More;
use Tests\TestCase;

/**
 * The admin's More screen (user's ask of 2026-11-25): no Profile tile (it is
 * the sidebar's user card), and the tiles in three rows in the user's order,
 * each tile kept on its own row on a wide screen.
 */
class AdminMoreTilesTest extends TestCase
{
    public function test_tiles_are_in_three_rows_in_the_users_order_without_profile(): void
    {
        $more = new More();
        $more->mount();

        $rows = [];
        foreach ($more->items as $item) {
            $rows[$item['row']][] = $item['title'];
        }

        $this->assertSame([
            1 => ['Messaging', 'Push Notification', 'Reception', 'Gatepass', 'Users', 'Boards'],
            2 => ['Admissions', 'Website Data', 'Exam Copy', 'Complaints', 'Contact Admin', 'Setting'],
            3 => ['Rules & Regulation', 'About App', 'Rate LMS', 'Terms & Conditions', 'Privacy Policy', 'Terms Of Use'],
        ], $rows);
        $this->assertNotContains('admin.profile', array_column($more->items, 'route'));

        // Each tile carries its row for the wide-screen grid.
        $html = view('livewire.admin.more', ['items' => $more->items, 'organization' => 9])->render();
        $this->assertStringContainsString('grid-row: var(--row)', $html);
        $this->assertSame(6, substr_count($html, '--row: 1;'));
        $this->assertSame(6, substr_count($html, '--row: 3;'));
        $this->assertStringNotContainsString('>Profile<', $html);
    }
}
