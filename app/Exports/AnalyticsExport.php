<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalyticsExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $exportData = [];

        // Statistics Section
        $exportData[] = ['STATISTICS SUMMARY'];
        $exportData[] = ['Metric', 'Count', 'Growth %', 'Trend'];
        $exportData[] = [
            'Total Students',
            $this->data['stats']['total_students']['count'],
            $this->data['stats']['total_students']['growth'] . '%',
            $this->data['stats']['total_students']['trend']
        ];
        $exportData[] = [
            'Active ID Cards',
            $this->data['stats']['active_id_cards']['count'],
            $this->data['stats']['active_id_cards']['growth'] . '%',
            $this->data['stats']['active_id_cards']['trend']
        ];
        $exportData[] = [
            'Expiring Soon',
            $this->data['stats']['expiring_soon']['count'],
            $this->data['stats']['expiring_soon']['growth'] . '%',
            $this->data['stats']['expiring_soon']['trend']
        ];
        $exportData[] = [
            'New Admissions',
            $this->data['stats']['new_admissions']['count'],
            $this->data['stats']['new_admissions']['growth'] . '%',
            $this->data['stats']['new_admissions']['trend']
        ];
        $exportData[] = []; // Empty row

        // ID Card Status
        $exportData[] = ['ID CARD STATUS'];
        $exportData[] = ['Status', 'Count'];
        $exportData[] = ['Active', $this->data['id_card_status']['active']];
        $exportData[] = ['Expired', $this->data['id_card_status']['expired']];
        $exportData[] = ['Inactive', $this->data['id_card_status']['inactive']];
        $exportData[] = ['Pending', $this->data['id_card_status']['pending']];
        $exportData[] = []; // Empty row

        // Class Distribution
        $exportData[] = ['CLASS DISTRIBUTION'];
        $exportData[] = ['Class', 'Student Count'];
        foreach ($this->data['class_distribution'] as $class => $count) {
            $exportData[] = [$class, $count];
        }
        $exportData[] = []; // Empty row

        // Recent Activities
        $exportData[] = ['RECENT ACTIVITIES'];
        $exportData[] = ['Activity', 'Description', 'Time'];
        foreach ($this->data['recent_activities'] as $activity) {
            $exportData[] = [
                $activity['title'],
                $activity['description'],
                $activity['time']
            ];
        }

        return $exportData;
    }

    public function headings(): array
    {
        return [
            ['Analytics Report - ' . $this->data['period']],
            ['Exported on: ' . $this->data['exported_at']],
            []
        ];
    }

    public function title(): string
    {
        return 'Analytics Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true, 'size' => 12]],

            // Style section headers
            'A4' => ['font' => ['bold' => true, 'size' => 14]],
            'A11' => ['font' => ['bold' => true, 'size' => 14]],
            'A18' => ['font' => ['bold' => true, 'size' => 14]],
            'A26' => ['font' => ['bold' => true, 'size' => 14]],

            // Style table headers
            'A5:A10' => ['font' => ['bold' => true]],
            'A12:A16' => ['font' => ['bold' => true]],
            'A19:A24' => ['font' => ['bold' => true]],
            'A27:A30' => ['font' => ['bold' => true]],
        ];
    }
}
