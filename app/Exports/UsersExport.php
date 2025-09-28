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

class UsersExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Country',
            'Player Name',
            'Alliance',
            'World',
            'Points',
            'Villages',
            'Status',
            'Last Active',
            'Created At',
        ];
    }

    public function map($user): array
    {
        $player = $user->player;

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? 'N/A',
            $user->phone_country ?? 'N/A',
            $player ? $player->name : 'N/A',
            $player && $player->alliance ? $player->alliance->name : 'N/A',
            $player && $player->world ? $player->world->name : 'N/A',
            $player ? number_format($player->points) : '0',
            $player ? $player->villages->count() : '0',
            $this->getUserStatus($user, $player),
            $player ? $player->last_active_at?->format('Y-m-d H:i:s') : 'N/A',
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 20,  // Name
            'C' => 30,  // Email
            'D' => 15,  // Phone
            'E' => 10,  // Country
            'F' => 20,  // Player Name
            'G' => 20,  // Alliance
            'H' => 15,  // World
            'I' => 12,  // Points
            'J' => 10,  // Villages
            'K' => 12,  // Status
            'L' => 20,  // Last Active
            'M' => 20,  // Created At
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
            'A:M' => [
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
                $sheet->setAutoFilter('A1:M1');

                // Freeze first row
                $sheet->freezePane('A2');

                // Add summary row
                $lastRow = $sheet->getHighestRow();
                $summaryRow = $lastRow + 2;

                $sheet->setCellValue("A{$summaryRow}", 'Total Users:');
                $sheet->setCellValue("B{$summaryRow}", $this->users->count());

                $activeUsers = $this->users->filter(function ($user) {
                    return $user->player && $user->player->is_active;
                })->count();

                $sheet->setCellValue('A'.($summaryRow + 1), 'Active Players:');
                $sheet->setCellValue('B'.($summaryRow + 1), $activeUsers);

                $sheet->getStyle("A{$summaryRow}:B".($summaryRow + 1))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6'],
                    ],
                ]);
            },
        ];
    }

    private function getUserStatus($user, $player)
    {
        if (! $player) {
            return 'No Player';
        }

        if (! $player->is_active) {
            return 'Inactive';
        }

        if ($player->is_online) {
            return 'Online';
        }

        return 'Offline';
    }
}
