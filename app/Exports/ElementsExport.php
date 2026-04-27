<?php

namespace App\Exports;

use App\Models\StructureElement;
use App\Models\StructureModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Illuminate\Support\Collection;

class ElementsExport
{
    public function __construct(
        protected Collection $elements,
        protected ?StructureModel $structureModel = null,
        protected ?int $procesoId = null
    ) {}

    /**
     * Genera el archivo Excel con los elementos filtrados.
     *
     * @return string Ruta del archivo temporal generado
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jerarquia');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

        $sheet->setCellValue('A1', 'Informe de jerarquia y enlaces');
        $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A1:E2')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:E2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F7FA');

        $headers = ['Elemento', 'Enlace'];
        $headerRow = 4;
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . $headerRow, $header);
            $column++;
        }

        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
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

        $rows = $this->buildRows();
        $row = $headerRow + 1;
        foreach ($rows as $line) {
            if ($line['type'] === 'section') {
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue("A{$row}", $line['label']);
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $line['color']],
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
                $row++;
                continue;
            }

            if ($line['type'] === 'spacer') {
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("E{$row}", $line['link_label'] ?? '');

                if (!empty($line['link_url'])) {
                    $sheet->getCell("E{$row}")->getHyperlink()->setUrl($line['link_url']);
                    $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB('FF0563C1');
                    $sheet->getStyle("E{$row}")->getFont()->setUnderline(true);
                }

                $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
                continue;
            }

            $sheet->setCellValue("A{$row}", $line['label']);
            $sheet->setCellValue("E{$row}", $line['link_label']);

            if (!empty($line['link_url'])) {
                $sheet->getCell("E{$row}")->getHyperlink()->setUrl($line['link_url']);
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB('FF0563C1');
                $sheet->getStyle("E{$row}")->getFont()->setUnderline(true);
            }

            $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A5');

        $fileName = 'elementos_' . now()->format('Y-m-d_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function buildRows(): array
    {
        $elements = $this->elements->sortBy('elemento_id')->values();

        if ($elements->isEmpty()) {
            return [[
                'type' => 'row',
                'label' => 'Sin estructura',
                'link_label' => 'Sin enlace',
                'link_url' => null,
            ]];
        }

        $nodes = $elements->keyBy('elemento_id');
        $roots = $elements->filter(fn (StructureElement $element) => empty($element->padre_id))->values();

        $rows = [];
        foreach ($roots as $root) {
            $this->appendNodeRows($rows, $root, $nodes, 1);
        }

        return $rows;
    }

    private function appendNodeRows(array &$rows, StructureElement $element, Collection $nodes, int $level): void
    {
        $rows[] = [
            'type' => 'section',
            'level' => $level,
            'label' => str_repeat('  ', max(0, $level - 1)) . $this->nodeLabel($element),
            'color' => $this->sectionColorForDepth($level),
        ];

        $slotIndex = count($rows);
        $rows[] = [
            'type' => 'spacer',
            'label' => '',
            'link_label' => '',
            'link_url' => null,
        ];

        if ($this->isLinkableType((string) ($element->tipo ?? ''))) {
            $links = collect($element->files ?? [])
                ->filter(fn ($file) => !$this->procesoId || (int) $file->proceso_id === $this->procesoId)
                ->map(function ($file) {
                    $url = $file->getPublicUrl() ?? $file->url;
                    return [
                        'label' => $file->nombre_original ?: ($url ?: 'Sin enlace'),
                        'url' => $url,
                    ];
                })
                ->values();

            if ($links->isNotEmpty()) {
                $first = $links->shift();
                $rows[$slotIndex]['link_label'] = $first['label'] ?? 'Enlace';
                $rows[$slotIndex]['link_url'] = $first['url'] ?? null;
            }

            foreach ($links as $link) {
                $rows[] = [
                    'type' => 'row',
                    'label' => '',
                    'link_label' => $link['label'] ?? 'Enlace',
                    'link_url' => $link['url'] ?? null,
                ];
            }
        }

        $children = $nodes->filter(fn (StructureElement $candidate) => (int) $candidate->padre_id === (int) $element->elemento_id)
            ->values();

        foreach ($children as $child) {
            $this->appendNodeRows($rows, $child, $nodes, $level + 1);
        }
    }

    private function nodeLabel(StructureElement $element): string
    {
        $nomenclatura = trim((string) ($element->nomenclatura ?? ''));
        $descripcion = trim((string) ($element->descripcion ?? $element->nombre ?? ''));

        if ($nomenclatura !== '' && $descripcion !== '') {
            return $nomenclatura . ' - ' . $descripcion;
        }

        return $nomenclatura !== '' ? $nomenclatura : ($descripcion !== '' ? $descripcion : ('Elemento #' . $element->elemento_id));
    }

    private function sectionColorForDepth(int $depth): string
    {
        return match (true) {
            $depth <= 1 => 'FF1F4E78',
            $depth === 2 => 'FF2F6C97',
            default => 'FF4E86B4',
        };
    }

    private function isLinkableType(string $tipo): bool
    {
        $tipo = strtolower(trim($tipo));
        if ($tipo === '') {
            return false;
        }

        $assignable = collect($this->structureModel?->tipos_asignables ?? [])
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter()
            ->values();

        if ($assignable->isNotEmpty()) {
            return $assignable->contains($tipo);
        }

        // Fallback de compatibilidad: si el modelo no define tipos_asignables,
        // asumimos el comportamiento histórico de SINAES flexible (solo fuente).
        return $tipo === 'fuente';
    }
}
