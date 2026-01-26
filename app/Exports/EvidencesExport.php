<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Collection;

class EvidencesExport
{
    protected Collection $evidences;

    public function __construct(Collection $evidences)
    {
        $this->evidences = $evidences;
    }

    /**
     * Genera el archivo Excel con las evidencias filtradas
     * 
     * @return string Ruta del archivo generado
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezados
        $headers = ['Nomenclatura', 'Descripción', 'Criterio', 'Estado', 'Responsables', 'Fecha Publicación'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Estilo para encabezados
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4CAF50']
            ]
        ]);

        // Llenar datos
        $row = 2;
        foreach ($this->evidences as $evidence) {
            $assignees = $evidence->assignments
                ->map(fn($assignment) => $assignment->user->nombre)
                ->join(', ');

            $sheet->setCellValue("A{$row}", $evidence->nomenclatura);
            $sheet->setCellValue("B{$row}", $evidence->descripcion);
            $sheet->setCellValue("C{$row}", $evidence->criterion->nomenclatura . ' - ' . $evidence->criterion->descripcion);
            $sheet->setCellValue("D{$row}", $evidence->evidenceState->nombre ?? 'N/A');
            $sheet->setCellValue("E{$row}", $assignees ?: 'Sin asignar');
            $sheet->setCellValue("F{$row}", $evidence->created_at->format('d/m/Y H:i'));
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo
        $fileName = 'evidencias_' . now()->format('Y-m-d_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}

