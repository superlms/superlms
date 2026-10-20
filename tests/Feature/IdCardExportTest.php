<?php

namespace Tests\Feature;

use App\Livewire\Admin\IdCard;
use App\Models\User;
use App\Support\IdCardExport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

/**
 * ID Card → Export: the school picks the columns and their order (kept for
 * the next export), and the sheet has them in that order — first column,
 * second… — each photo in its cell, numbers kept as text; the zip carries the
 * sheet and a photos/ folder named as the Photo File Name column says.
 * Students class by class; teachers; and management & staff without teachers.
 */
class IdCardExportTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('image')->nullable(); $t->string('mobile_number')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable(); $t->string('father_name')->nullable();
            $t->string('aadhar_no')->nullable(); $t->text('image')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->string('route_name')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('transportation_id'); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('type');
            $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->unsignedBigInteger('driver_detail_id')->nullable();
            $t->string('mobile')->nullable(); $t->string('designation')->nullable(); $t->string('photo')->nullable();
            $t->timestamps();
        });
        foreach (['student_id_cards' => 'student_detail_id', 'teacher_id_cards' => 'teacher_detail_id', 'employee_id_cards' => 'admin_employee_id'] as $table => $holder) {
            Schema::create($table, function (Blueprint $t) use ($holder) {
                $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger($holder);
                $t->string('card_number'); $t->date('issue_date')->nullable(); $t->date('expiry_date')->nullable();
                $t->string('status')->default('active'); $t->timestamps();
            });
        }

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));

        $six  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'VI', 'order' => 6]);
        $five = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'V', 'order' => 5]);
        $a    = DB::table('sections')->insertGetId(['standard_id' => $five, 'name' => 'A']);
        DB::table('student_details')->insert([
            ['organization_id' => $this->org, 'full_name' => 'Zoya Khan', 'admission_no' => 'ADM-2', 'aadhar_no' => '012345678901',
                'father_name' => 'Imran Khan', 'image' => $this->photo(), 'standard_id' => $five, 'section_id' => $a],
            ['organization_id' => $this->org, 'full_name' => 'Aarav Jain', 'admission_no' => 'ADM-1', 'aadhar_no' => null,
                'father_name' => 'Rohit Jain', 'image' => null, 'standard_id' => $six, 'section_id' => null],
            ['organization_id' => $this->org, 'full_name' => 'Another School', 'admission_no' => 'X-1', 'aadhar_no' => null,
                'father_name' => null, 'image' => null, 'standard_id' => $five, 'section_id' => $a],
        ]);
        DB::table('student_details')->where('full_name', 'Another School')->update(['organization_id' => 99]);
    }

    /** A small red photo, as a data: link (resolvePhoto passes those through). */
    private function photo(): string
    {
        $im = imagecreatetruecolor(30, 40);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 40, 40));
        ob_start();
        imagepng($im);

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    public function test_whose_data_comes_first_then_its_filters_and_the_cards_columns(): void
    {
        // Nothing picked yet: no filters, no columns, no download.
        $page = Livewire::test(IdCard::class)->call('openExport');
        $this->assertSame('', $page->get('exportType'));
        $this->assertSame([], $page->get('exportColumns'));
        $html = $page->html();
        $this->assertStringContainsString('1 · Whose data', $html);
        $this->assertStringNotContainsString('wire:model.live="exportStandard"', $html);
        $this->assertStringNotContainsString('id-card/export', $html);

        // Students: the class and section filters, and the card's own columns.
        $page->set('exportType', 'student');
        $this->assertSame(['sr_no', 'name', 'class', 'father_name', 'mother_name', 'dob', 'address', 'phone', 'photo'],
            $page->get('exportColumns'));
        $html = $page->html();
        $this->assertStringContainsString('wire:model.live="exportStandard"', $html);
        $this->assertStringNotContainsString('Add a column', $html);
        $this->assertStringNotContainsString('Preview', $html);
        $this->assertStringNotContainsString('Zoya Khan', $html);
        $this->assertMatchesRegularExpression('#<strong[^>]*>2</strong> students#', $html);

        // Teachers have no class filter.
        $page->set('exportType', 'teacher');
        $this->assertStringNotContainsString('wire:model.live="exportStandard"', $page->html());
        $this->assertSame(IdCardExport::defaults('teacher'), $page->get('exportColumns'));
    }

    public function test_the_panel_keeps_the_order_the_school_puts_the_columns_in(): void
    {
        $page = Livewire::test(IdCard::class)->call('openExport')->set('exportType', 'student');

        $page->call('removeExportColumn', 'sr_no')
            ->call('dropExportColumn', 'dob', 0)        // dragged to the front
            ->call('moveExportColumn', 'class', -1);    // one place earlier

        $cols = $page->get('exportColumns');
        $this->assertSame(['dob', 'class', 'name', 'father_name'], array_slice($cols, 0, 4));
        $this->assertNotContains('sr_no', $cols);
        // Kept for the next export; Reset brings the card's columns back.
        $this->assertSame($cols, IdCardExport::savedColumns($this->org, 'student'));
        $page->call('resetExportColumns');
        $this->assertSame(IdCardExport::defaults('student'), $page->get('exportColumns'));
    }

    public function test_a_phone_photo_turned_on_its_side_comes_out_upright(): void
    {
        // A 40 × 30 picture whose EXIF says "turn 90° clockwise" (6): seen 30 × 40.
        $im = imagecreatetruecolor(40, 30);
        ob_start();
        imagejpeg($im);
        $jpeg = ob_get_clean();
        $tiff = 'MM' . pack('n', 42) . pack('N', 8) . pack('n', 1)
            . pack('n', 0x0112) . pack('n', 3) . pack('N', 1) . pack('n', 6) . pack('n', 0) . pack('N', 0);
        $app1 = "\xFF\xE1" . pack('n', strlen($tiff) + 8) . "Exif\0\0" . $tiff;
        $rotated = substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);

        $this->assertSame(6, \App\Support\PhotoOrientation::of($rotated));
        $this->assertSame(1, \App\Support\PhotoOrientation::of($jpeg));

        $up = \App\Support\PhotoOrientation::upright(imagecreatefromstring($rotated), 6);
        $this->assertSame([30, 40], [imagesx($up), imagesy($up)]);
    }

    public function test_the_sheet_has_the_columns_in_order_with_each_photo_in_its_cell(): void
    {
        [$path, $name, , $work] = IdCardExport::build($this->org, 'Delhi Public School', 'student',
            ['aadhar_no', 'name', 'photo', 'class', 'father_name'], null, null, 'all', 'xlsx');

        $sheet = IOFactory::load($path)->getActiveSheet();
        $this->assertSame(['Aadhaar No', 'Name', 'Photo', 'Class', "Father's Name"],
            [$sheet->getCell('A1')->getValue(), $sheet->getCell('B1')->getValue(), $sheet->getCell('C1')->getValue(),
                $sheet->getCell('D1')->getValue(), $sheet->getCell('E1')->getValue()]);
        // Class order, the Aadhaar number as text with its leading zero.
        $this->assertSame('012345678901', $sheet->getCell('A2')->getValue());
        $this->assertSame('Zoya Khan', $sheet->getCell('B2')->getValue());
        $this->assertSame('Aarav Jain', $sheet->getCell('B3')->getValue());
        // The class carries its section's last letter; no section, the bare class.
        $this->assertSame('V (A)', $sheet->getCell('D2')->getValue());
        $this->assertSame('VI', $sheet->getCell('D3')->getValue());
        $this->assertSame(3, $sheet->getHighestRow());
        // One photo (Aarav has none), over the Photo cell of Zoya's row.
        $drawings = $sheet->getDrawingCollection();
        $this->assertCount(1, $drawings);
        $this->assertSame('C2', $drawings[0]->getCoordinates());
        $this->assertSame('delhi-public-school_students_all_' . now()->format('Y-m-d') . '.xlsx', $name);

        IdCardExport::cleanup($work);
        $this->assertDirectoryDoesNotExist($work);
    }

    public function test_the_class_carries_the_last_letter_of_its_section(): void
    {
        $this->assertSame('V (A)', IdCardExport::classWithSection('V', 'A'));
        $this->assertSame('Class 10 (B)', IdCardExport::classWithSection('Class 10', 'Section B'));
        $this->assertSame('IX (C)', IdCardExport::classWithSection('IX', 'sec-c.'));
        $this->assertSame('VI', IdCardExport::classWithSection('VI', ''));
    }

    public function test_the_zip_has_the_sheet_and_the_photos_named_as_the_sheet_says(): void
    {
        [$path, $name, $mime, $work] = IdCardExport::build($this->org, 'Delhi Public School', 'student',
            ['name', 'photo_file'], null, null, 'all', 'zip');

        $this->assertSame('application/zip', $mime);
        $zip = new ZipArchive();
        $zip->open($path);
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }
        $sheet = $zip->getFromName(str_replace('.zip', '.xlsx', $name));
        $zip->close();

        $this->assertContains('photos/ADM-2.jpg', $files);
        $this->assertCount(2, $files);
        $this->assertNotFalse($sheet);

        IdCardExport::cleanup($work);
    }

    public function test_staff_leaves_teachers_out_and_a_class_narrows_the_students(): void
    {
        DB::table('admin_employees')->insert([
            ['organization_id' => $this->org, 'name' => 'Mohan Lal', 'type' => 'management', 'designation' => 'Principal'],
            ['organization_id' => $this->org, 'name' => 'Anita Verma', 'type' => 'teacher', 'designation' => null],
        ]);

        $staff = IdCardExport::people($this->org, 'employee');
        $this->assertSame(['Mohan Lal'], $staff->pluck('name')->all());
        $this->assertSame([['name' => 'Mohan Lal', 'designation' => 'Principal']],
            IdCardExport::rows($this->org, 'employee', $staff, ['name', 'designation']));

        $five = DB::table('standards')->where('name', 'V')->value('id');
        $this->assertSame(['Zoya Khan'], IdCardExport::people($this->org, 'student', $five)->pluck('full_name')->all());

        // Issued / not issued: by an active card.
        $zoya = DB::table('student_details')->where('full_name', 'Zoya Khan')->value('id');
        DB::table('student_id_cards')->insert(['organization_id' => $this->org, 'student_detail_id' => $zoya, 'card_number' => 'ID-1', 'status' => 'active']);
        $this->assertSame(['Zoya Khan'], IdCardExport::people($this->org, 'student', null, null, 'issued')->pluck('full_name')->all());
        $this->assertSame(1, IdCardExport::count($this->org, 'student', null, null, 'not_issued'));
    }
}
