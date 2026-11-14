<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Student;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Super Admin → Students → Export: any school's students, as the school's
 * admin exports them — the same Excel sheet and PDF (App\Support\StudentExport),
 * the whole school or one class / section of it. Built on the admin export
 * test's school (4, "The Demo School": Aarav and Bina in Class 1 A / B,
 * Chetan in Class 2; Class 3 "Elsewhere" is school 99's).
 */
class SuperAdminStudentExportTest extends AdminStudentExportTest
{
    /** The inherited admin tests stay the admin's; these sign in as the Super Admin. */
    private function asSuperAdmin(): void
    {
        $super = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $super->id = 1;
        $super->role = 'super-admin';
        $this->actingAs($super);
    }

    private function body(StreamedResponse $res): string
    {
        ob_start();
        $res->sendContent();

        return (string) ob_get_clean();
    }

    public function test_excel_is_the_admins_sheet_for_the_school_picked(): void
    {
        $this->asSuperAdmin();
        $page = new Student();
        $page->exportSchool = '4';
        $page->showExportPicker = true;

        $res = $page->exportStudents();

        $this->assertInstanceOf(StreamedResponse::class, $res);
        $this->assertFalse($page->showExportPicker);
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/students_the-demo-school_all_\d{4}-\d{2}-\d{2}\.xlsx/', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $this->body($res));
    }

    public function test_pdf_is_the_admins_list_and_a_class_or_section_narrows_it(): void
    {
        $this->asSuperAdmin();
        $page = new Student();
        $page->exportSchool  = '4';
        $page->exportScope   = 'class';
        $page->exportClass   = '1';
        $page->exportSection = '11';

        $res = $page->exportStudentsPdf();

        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('students_the-demo-school_class-1-b_', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $this->body($res));
    }

    public function test_no_school_or_another_schools_class_is_refused(): void
    {
        $this->asSuperAdmin();
        $page = new Student();
        $this->assertNull($page->exportStudents());
        $this->assertTrue($page->getErrorBag()->has('exportSchool'));

        $page = new Student();
        $page->exportSchool = '4';
        $page->exportScope  = 'class';
        $page->exportClass  = '3';   // school 99's
        $this->assertNull($page->exportStudentsPdf());
        $this->assertTrue($page->getErrorBag()->has('exportClass'));

        $page->exportClass = '';
        $this->assertNull($page->exportStudents());
        $this->assertTrue($page->getErrorBag()->has('exportClass'));
    }

    public function test_a_sub_super_admin_exports_only_their_school(): void
    {
        $sub = new User(['name' => 'Sub', 'email' => 'sub@example.com']);
        $sub->id = 2;
        $sub->role = 'sub-super-admin';
        $sub->allowed_organization_id = 4;
        $this->actingAs($sub);

        $page = new Student();
        $page->exportSchool = '99';   // asked for another school
        $res = $page->exportStudents();

        $this->assertStringContainsString('students_the-demo-school_all_', $res->headers->get('Content-Disposition'));
    }
}
