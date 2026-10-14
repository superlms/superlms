<?php

namespace Tests\Feature;

use App\Models\Student\AdmitCard;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A new admit card number is never one already taken (2026-10-14: issuing
 * F.A 1 cards at "SWATANTRATA SENANI INTER COLLEGE" stopped on "Duplicate
 * entry 'ADMITSWAF.A261420' for key admit_cards_admit_card_number_unique" —
 * every "F.A …" exam of a "SWA…" school draws from the same 9,000 numbers).
 */
class AdmitCardNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 12:00:00');

        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('exam_name'); $t->timestamps();
        });
        Schema::create('admit_cards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('admit_card_number')->unique()->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 6, 'name' => 'SWATANTRATA SENANI INTER COLLEGE']);
        DB::table('exams')->insert([
            ['id' => 25, 'organization_id' => 6, 'exam_name' => 'F.A 1'],
            ['id' => 26, 'organization_id' => 6, 'exam_name' => 'F.A 2'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_number_keeps_its_form_and_is_not_taken(): void
    {
        $number = AdmitCard::generateAdmitCardNumber(6, 25);
        $this->assertMatchesRegularExpression('/^ADMITSWAF\.A26\d{4}$/', $number);
        // F.A 2 shares the base — the clash the school ran into.
        $this->assertStringStartsWith('ADMITSWAF.A26', AdmitCard::generateAdmitCardNumber(6, 26));
    }

    public function test_a_full_base_moves_on_to_a_longer_unused_number(): void
    {
        // Every four-digit number of the base is taken already.
        foreach (array_chunk(range(1000, 9999), 500) as $chunk) {
            DB::table('admit_cards')->insert(array_map(fn ($n) => ['organization_id' => 6, 'admit_card_number' => 'ADMITSWAF.A26' . $n], $chunk));
        }

        $numbers = [];
        for ($i = 0; $i < 30; $i++) {
            $number = AdmitCard::generateAdmitCardNumber(6, 26);
            $this->assertMatchesRegularExpression('/^ADMITSWAF\.A26\d{6}$/', $number);
            // Saved, as issuing does — the unique key takes it.
            DB::table('admit_cards')->insert(['organization_id' => 6, 'admit_card_number' => $number]);
            $numbers[] = $number;
        }
        $this->assertCount(30, array_unique($numbers));
    }

    public function test_a_crowded_base_still_finds_the_numbers_left(): void
    {
        // All but ten of the four-digit numbers are taken.
        $free = [1111, 2222, 3333, 4444, 5555, 6666, 7777, 8888, 9999, 1000];
        $taken = array_values(array_diff(range(1000, 9999), $free));
        foreach (array_chunk($taken, 500) as $chunk) {
            DB::table('admit_cards')->insert(array_map(fn ($n) => ['organization_id' => 6, 'admit_card_number' => 'ADMITSWAF.A26' . $n], $chunk));
        }

        for ($i = 0; $i < 20; $i++) {
            $number = AdmitCard::generateAdmitCardNumber(6, 25);
            $this->assertFalse(DB::table('admit_cards')->where('admit_card_number', $number)->exists(), $number);
            DB::table('admit_cards')->insert(['organization_id' => 6, 'admit_card_number' => $number]);
        }
    }
}
