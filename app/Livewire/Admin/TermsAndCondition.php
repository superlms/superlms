<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Admin\TermAndCondition;

class TermsAndCondition extends Component
{
    public $termsData;
    public $hasData = false;

    // Basic Information
    public $platformName;
    public $companyName;
    public $companyCin;
    public $effectiveDate;
    public $platformLogo;

    // Terms Content
    public $sections = [];
    public $additionalInfo = [];
    public $files = [];
    public $contactEmail;

    public function mount()
    {
        $this->termsData = TermAndCondition::first();

        if ($this->termsData) {
            $this->hasData = true;

            // Basic information
            $this->platformName = $this->termsData->platform_name ?? 'Terms & Conditions';
            $this->companyName = $this->termsData->company_name ?? '';
            $this->companyCin = $this->termsData->company_cin ?? '';
            $this->platformLogo = $this->termsData->platform_logo ?? null;

            // Format effective date
            if ($this->termsData->last_updated) {
                $date = $this->termsData->last_updated;
                $day = $date->format('jS');
                $month = $date->format('F');
                $year = $date->format('Y');
                $this->effectiveDate = "{$day} {$month} {$year}";
            } else {
                $this->effectiveDate = 'Not set';
            }

            // Extract metadata
            $metadata = $this->termsData->metadata ?? [];

            // Sections from metadata
            $this->sections = $metadata['sections'] ?? [];

            // Additional info from metadata
            $this->additionalInfo = $metadata['additional_info'] ?? [];

            // Files from metadata
            $this->files = $metadata['files'] ?? [];

            // Contact email
            $this->contactEmail = $metadata['contact_email'] ?? 'support@example.com';
        }
    }

    public function render()
    {
        return view('livewire.admin.terms-and-condition');
    }
}
