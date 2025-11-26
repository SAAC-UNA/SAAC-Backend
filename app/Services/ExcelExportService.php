<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportService
{
    public function generateAuditLogExcel($logs)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headings
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'User');
        $sheet->setCellValue('C1', 'Action');
        $sheet->setCellValue('D1', 'Module');
        $sheet->setCellValue('E1', 'Detail');
        $sheet->setCellValue('F1', 'Date/Time');

        // Content
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue("A{$row}", $log->bitacora_id);
            $sheet->setCellValue("B{$row}", $log->user->nombre ?? 'N/A');
            $sheet->setCellValue("C{$row}", $log->actionType->descripcion ?? 'N/A');
            $sheet->setCellValue("D{$row}", $log->modulo);
            $sheet->setCellValue("E{$row}", $log->detalle);
            $sheet->setCellValue("F{$row}", $log->fecha_hora);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save in storage/app
        $fileName = 'audit_log.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
