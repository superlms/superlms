<?php

namespace App\Livewire\Concerns;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Super Admin → Students / Teachers → Export: the school admin's export, for
 * any school. Export opens the admin's "Export as…" box (x-admin.export-picker)
 * with a School to pick first (the list's school filter, when one is set);
 * then Excel or PDF downloads exactly the file that school's admin gets
 * (App\Support\StudentExport / TeacherExport). The actions are named
 * export…, so the panel's download loader shows its card and bar meanwhile.
 *
 * A sub super-admin limited to one school can export only that school.
 */
trait SuperAdminSchoolExport
{
    public bool   $showExportPicker = false;
    public string $exportSchool     = '';

    /** The one school a sub super-admin is limited to, if any. */
    protected function exportLockedSchool(): ?int
    {
        $user = Auth::user();

        return $user && method_exists($user, 'isSubSuperAdmin') && $user->isSubSuperAdmin()
            ? $user->allowedOrganizationId()
            : null;
    }

    /** The schools the box offers. */
    protected function exportSchools()
    {
        $locked = $this->exportLockedSchool();

        return Organization::query()
            ->when($locked, fn ($q) => $q->whereKey($locked))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** The school picked for the export, or null (with the error shown) when none is. */
    protected function exportSchoolId(): ?int
    {
        $id = $this->exportLockedSchool() ?: (int) $this->exportSchool;

        if (!$id || !Organization::whereKey($id)->exists()) {
            $this->addError('exportSchool', 'Please choose a school to export.');

            return null;
        }
        $this->resetErrorBag('exportSchool');

        return $id;
    }

    /** "doon-public-school" — the school's part of the file name. */
    protected function exportSchoolSlug(int $orgId): string
    {
        return Str::slug((string) Organization::whereKey($orgId)->value('name')) ?: 'school';
    }

    public function closeExportPicker(): void
    {
        $this->showExportPicker = false;
    }
}
