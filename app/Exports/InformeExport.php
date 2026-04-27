<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InformeExport
{
    public function __construct(
        protected array $report
    ) {}

    public function generate(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $this->buildHierarchySheet($spreadsheet->getActiveSheet());

        $fileName = 'informe_'.now()->format('Y-m-d_His').'.xlsx';
        $tempPath = storage_path('app/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function buildHierarchySheet($sheet): void
    {
        $sheet->setTitle('Jerarquia');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

        $sheet->setCellValue('A1', 'Tabla de enlaces para el informe de acreditación');
        $sheet->setCellValue('A2', 'Generado por SAAC el: '.($this->report['generated_at_human'] ?? now()->format('d/m/Y H:i')));

        $sheet->getStyle('A1:B2')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:B2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F7FA');

        $headerRow = 4;
        $headers = ['Elemento', 'Enlace'];
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column.$headerRow, $header);
        }

        $sheet->getStyle("A{$headerRow}:B{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFB7C9D6'],
                ],
            ],
        ]);

        $row = $headerRow + 1;

        if (empty($this->report['dimensions'])) {
            $sheet->setCellValue("A{$row}", 'No hay estructura disponible para los filtros seleccionados.');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            $row = $this->writeHierarchyRows($sheet, $row, $this->report['dimensions']);
        }

        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A5');
    }

    private function writeHierarchyRows($sheet, int $row, array $dimensions): int
    {
        $inlineLeafLinks = ($this->report['layout'] ?? null) === 'leaf_links_inline';

        foreach ($dimensions as $dimension) {
            $row = $this->writeSectionHeader($sheet, $row, $dimension['label'] ?? 'Sin dimensión', 1, 'FFD4E1F9', 'FF000000');

            foreach ($dimension['components'] ?? [] as $component) {
                $row = $this->writeSectionHeader($sheet, $row, $component['label'] ?? 'Sin componente', 2, 'FFE8F4E6', 'FF000000');

                foreach ($component['criteria'] ?? [] as $criterion) {
                    if ($inlineLeafLinks) {
                        $links = collect($criterion['evidences'] ?? [])
                            ->flatMap(fn ($evidence) => $evidence['links'] ?? [])
                            ->values();

                        $firstLink = $links->first();

                        $sheet->setCellValue("A{$row}", $criterion['label'] ?? 'Sin criterio');
                        $sheet->setCellValue("B{$row}", $firstLink['label'] ?? 'Sin enlace');

                        if (! empty($firstLink['url'])) {
                            $sheet->getCell("B{$row}")->getHyperlink()->setUrl($firstLink['url']);
                            $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF0563C1');
                            $sheet->getStyle("B{$row}")->getFont()->setUnderline(true);
                        }

                        $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                        $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setWrapText(true);
                        $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        $row++;

                        foreach ($links->slice(1) as $extraLink) {
                            $sheet->setCellValue("A{$row}", '');
                            $sheet->setCellValue("B{$row}", $extraLink['label'] ?? 'Sin enlace');

                            if (! empty($extraLink['url'])) {
                                $sheet->getCell("B{$row}")->getHyperlink()->setUrl($extraLink['url']);
                                $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF0563C1');
                                $sheet->getStyle("B{$row}")->getFont()->setUnderline(true);
                            }

                            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setWrapText(true);
                            $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                            $row++;
                        }

                        continue;
                    }

                    $row = $this->writeSectionHeader($sheet, $row, $criterion['label'] ?? 'Sin criterio', 3, 'FFFFF0D4', 'FF000000');

                    $evidences = $criterion['evidences'] ?? [];

                    if (empty($evidences)) {
                        continue;
                    }

                    foreach ($evidences as $idx => $evidence) {
                        $links = $evidence['links'] ?: [['label' => 'Sin enlace', 'url' => null]];

                        foreach ($links as $linkIdx => $link) {
                            // Evidencia y Enlace row
                            $evidenciaLabel = trim(
                                ($evidence['nomenclatura'] ?? 'Sin evidencia').
                                (! empty($evidence['descripcion']) ? ' - '.$evidence['descripcion'] : '')
                            );
                            // If it starts with "Evidencia: ", keep it, otherwise do not add it.
                            // The user requested not to force "Evidencia:" if the structure doesn't have evidences.
                            if (! str_starts_with($evidenciaLabel, 'Evidencia: ') && ! str_starts_with($evidenciaLabel, 'Elemento #')) {
                                // Assume it's just the nomenclature
                                $evidenciaLabel = $evidenciaLabel;
                            }

                            $sheet->setCellValue("A{$row}", $evidenciaLabel);
                            $sheet->setCellValue("B{$row}", $link['label'] ?? $link['url'] ?? 'Sin enlace');

                            if (! empty($link['url'])) {
                                $sheet->getCell("B{$row}")->getHyperlink()->setUrl($link['url']);
                                $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF0563C1');
                                $sheet->getStyle("B{$row}")->getFont()->setUnderline(true);
                            }

                            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setWrapText(true);
                            $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                            $row++;
                        }
                    }
                }
            }
        }

        return $row;
    }

    private function writeSectionHeader($sheet, int $row, string $label, int $level, string $fillColor, string $fontColor = 'FFFFFFFF'): int
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", str_repeat('  ', max(0, $level - 1)).$label);
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => $fontColor]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $fillColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFB7C9D6'],
                ],
            ],
        ]);

        return $row + 1;
    }

    private function writeSpacerRow($sheet, int $row): int
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", '');
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFFFF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE5E7EB'],
                ],
            ],
        ]);

        return $row + 1;
    }
}
