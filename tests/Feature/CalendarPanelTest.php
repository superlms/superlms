<?php

namespace Tests\Feature;

use App\Livewire\Admin\TimeTableCalendar;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * School Calendar (admin and accounts): the page sits on the grey every page
 * has (not the layout's coloured background), its header stays whole at the
 * top while scrolling (data-lms-pin), and an event's details are the Students
 * view's single column of label / value rows.
 */
class CalendarPanelTest extends TestCase
{
    private int $org = 11;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 10:00:00');
        config(['app.key' => 'base64:' . base64_encode(str_repeat('c', 32))]);

        Schema::create('time_tables', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->string('title');
            $t->text('description')->nullable();
            $t->date('date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->string('event_type')->nullable();
            $t->string('color')->nullable();
            $t->string('attachment')->nullable();
            $t->string('recurrence')->nullable();
            $t->date('recurrence_end_date')->nullable();
            $t->boolean('is_all_day')->default(false);
            $t->boolean('is_cancelled')->default(false);
            $t->string('cancellation_reason')->nullable();
            $t->timestamps();
        });
        foreach (['time_table_academics', 'time_table_locations'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('time_table_id');
                $t->timestamps();
            });
        }

        DB::table('time_tables')->insert([
            'organization_id' => $this->org, 'title' => 'Annual Sports Day', 'description' => "Track events\nPrize giving",
            'date' => '2026-10-10', 'start_time' => '09:00:00', 'end_time' => '13:00:00', 'event_type' => 'event',
        ]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_page_is_on_grey_and_its_header_stays_whole(): void
    {
        $html = Livewire::test(TimeTableCalendar::class)->html();

        $this->assertMatchesRegularExpression('/^<div[^>]*class="min-h-screen bg-gray-50[ "]/', trim($html));
        $this->assertMatchesRegularExpression('/sticky top-0 z-50[^"]*" data-lms-pin>/', $html);

        foreach (['admin/calender', 'accounts/calendar'] as $wrapper) {
            $blade = file_get_contents(resource_path("views/livewire/{$wrapper}.blade.php"));
            $this->assertStringStartsWith('<div class="min-h-screen bg-gray-50">', $blade);
        }
        $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        // A pinned header stays whole (since 22088b8, so does every Super Admin header: PIN_ALL).
        $this->assertStringContainsString("if (PIN_ALL || header.hasAttribute('data-lms-pin')) return 0;", $layout);
    }

    /**
     * The Super Admin's Calendar look (2026-11-27): the month on the left, the
     * month's events listed on the right; a day shows its events as plain lines
     * (a dot, the time, the title), no background behind them.
     */
    public function test_the_month_on_the_left_and_its_events_listed_on_the_right(): void
    {
        DB::table('time_tables')->insert([
            ['organization_id' => $this->org, 'title' => 'PTM', 'date' => '2026-10-02', 'start_time' => '11:00:00', 'end_time' => null, 'event_type' => 'meeting', 'is_all_day' => false],
            ['organization_id' => $this->org, 'title' => 'Diwali Holiday', 'date' => '2026-10-20', 'start_time' => null, 'end_time' => null, 'event_type' => 'holiday', 'is_all_day' => true],
            ['organization_id' => $this->org, 'title' => 'November Fair', 'date' => '2026-11-03', 'start_time' => null, 'end_time' => null, 'event_type' => 'event', 'is_all_day' => true],
        ]);

        $html = Livewire::test(TimeTableCalendar::class)->html();

        // The page fits the window on a wide screen; the month and the list side by side.
        $this->assertStringContainsString('xl:h-dvh', $html);
        $this->assertStringContainsString('xl:grid-cols-[minmax(0,1fr)_21rem]', $html);
        $html  = preg_replace('/<!--.*?-->/s', '', $html);   // Livewire's block markers
        $month = substr($html, strpos($html, 'data-cal-month'), strpos($html, 'data-cal-list') - strpos($html, 'data-cal-month'));
        $list  = substr($html, strpos($html, 'data-cal-list'));

        // The day: plain lines, no tinted background.
        $this->assertSame(3, substr_count($month, 'class="cal-ev'));
        $this->assertStringNotContainsString('18;"', $month);
        $this->assertMatchesRegularExpression('/<span class="font-semibold">9:00<\/span> Annual Sports Day/', $month);
        $this->assertStringNotContainsString('November Fair', $month);

        // The list: every event of October in date order, past ones marked Done.
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($list)));
        $this->assertStringContainsString('Events October 2026 · 3 events', $text);
        $this->assertMatchesRegularExpression('/Fri 2 PTM Done 11:00 AM · Meeting.*Sat 10 Annual Sports Day 9:00 AM – 1:00 PM · Event.*Tue 20 Diwali Holiday All day · Holiday/', $text);
        $this->assertStringNotContainsString('November Fair', $text);
        $this->assertStringContainsString('wire:click="onEventClick(', $list);

        // The old Upcoming / Completed tables under the month are gone.
        $this->assertStringNotContainsString('Upcoming Events', $html);
    }

    public function test_an_events_details_are_one_column_of_label_and_value_rows(): void
    {
        $id = DB::table('time_tables')->value('id');

        $html = Livewire::test(TimeTableCalendar::class)
            ->call('onEventClick', $id)
            ->assertSet('showSlider', true)
            ->html();

        $panel = substr($html, strpos($html, 'Event Details'));
        $this->assertStringContainsString('<span class="text-xs text-gray-400 uppercase tracking-wider">Title</span>', $panel);
        $this->assertStringContainsString('<span class="col-span-2 text-gray-800 font-medium">Annual Sports Day</span>', $panel);
        $this->assertStringContainsString('<span class="text-xs text-gray-400 uppercase tracking-wider">Description</span>', $panel);
        $this->assertStringContainsString('09:00 – 13:00', $panel);
        $this->assertStringNotContainsString('sm:grid-cols-2', $panel);
    }
}
