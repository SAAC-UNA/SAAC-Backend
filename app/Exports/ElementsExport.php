<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class ElementsExport
{
    protected Collection $elements;

    public function __construct(Collection $elements)
    {
        $this->elements = $elements;
    }

    /**
     * Genera el archivo Excel con los elementos filtrados.
     *
     * @return string Ruta del archivo temporal generado
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Elementos');

        // Encabezados
        $headers = [
            'Nomenclatura', 'Descripción', 'Tipo', 'Categoría',
            'Estado', 'Activo', 'Padre', 'Fecha Límite', 'Responsables',
        ];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Estilo encabezados (azul del sistema flexible)
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1976D2'],
            ],
        ]);

        // Datos
        $row = 2;
        foreach ($this->elements as $element) {
            $responsables = $element->assignments
                ->map(fn($a) => $a->user->nombre ?? $a->user->name ?? '')
                ->filter()
                ->unique()
                ->join(', ');

            $sheet->setCellValue("A{$row}", $element->nomenclatura ?? '');
            $sheet->setCellValue("B{$row}", $element->descripcion ?? '');
            $sheet->setCellValue("C{$row}", $element->tipo ?? '');
            $sheet->setCellValue("D{$row}", $element->categoria ?? '');
            $sheet->setCellValue("E{$row}", $element->estado ?? 'N/A');
            $sheet->setCellValue("F{$row}", $element->activo ? 'Sí' : 'No');
            $sheet->setCellValue("G{$row}", $element->parent?->nomenclatura ?? '—');
            $sheet->setCellValue("H{$row}", $element->fecha_limite?->format('d/m/Y') ?? '—');
            $sheet->setCellValue("I{$row}", $responsables ?: 'Sin asignar');
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo temporal
        $fileName = 'elementos_' . now()->format('Y-m-d_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
