<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $reports;

    protected $world;

    public function __construct($reports, $world = null)
    {
        $this->reports = $reports;
        $this->world = $world;
    }

    public function collection()
    {
        return $this->reports;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Status',
            'Attacker',
            'Defender',
            'Coordinates',
            'Resources Looted',
            'Casualties',
            'Duration',
            'Date',
            'World',
        ];
    }

    public function map($report): array
    {
        return [
            $report->id,
            ucfirst($report->type),
            ucfirst($report->status),
            $report->attacker_name ?? 'N/A',
            $report->defender_name ?? 'N/A',
            $report->coordinates ?? 'N/A',
            $this->formatResources($report->resources_looted ?? []),
            $this->formatCasualties($report->casualties ?? []),
            $report->duration ?? 'N/A',
            $report->created_at->format('Y-m-d H:i:s'),
            $this->world ? $this->world->name : 'N/A',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 12,  // Type
            'C' => 12,  // Status
            'D' => 20,  // Attacker
            'E' => 20,  // Defender
            'F' => 15,  // Coordinates
            'G' => 25,  // Resources
            'H' => 25,  // Casualties
            'I' => 12,  // Duration
            'J' => 20,  // Date
            'K' => 15,  // World
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '366092'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Data rows
            'A:K' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Auto-filter
                $sheet->setAutoFilter('A1:K1');

                // Freeze first row
                $sheet->freezePane('A2');

                // Add summary row
                $lastRow = $sheet->getHighestRow();
                $summaryRow = $lastRow + 2;

                $sheet->setCellValue("A{$summaryRow}", 'Total Reports:');
                $sheet->setCellValue("B{$summaryRow}", $this->reports->count());

                $sheet->getStyle("A{$summaryRow}:B{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6'],
                    ],
                ]);
            },
        ];
    }

    private function formatResources($resources)
    {
        if (empty($resources)) {
            return 'None';
        }

        $formatted = [];
        foreach ($resources as $type => $amount) {
            if ($amount > 0) {
                $formatted[] = ucfirst($type).': '.number_format($amount);
            }
        }

        return implode(', ', $formatted);
    }

    private function formatCasualties($casualties)
    {
        if (empty($casualties)) {
            return 'None';
        }

        $formatted = [];
        foreach ($casualties as $unit => $count) {
            if ($count > 0) {
                $formatted[] = ucfirst($unit).': '.number_format($count);
            }
        }

        return implode(', ', $formatted);
    }
}
