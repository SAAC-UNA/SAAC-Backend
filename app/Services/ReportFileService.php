<?php

namespace App\Services;

use App\Models\ReportFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportFileService
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('saac.report_storage_disk', 'simulated_nas_reports');
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function uploadFile(UploadedFile $file, int $usuarioId, int $procesoId, string $tipo = 'Informes Universitarios'): ReportFile
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = (string) Str::uuid();
        $filename = "{$uuid}.{$extension}";

        $path = Storage::disk($this->disk)->putFileAs('', $file, $filename);

        $archivo = ReportFile::create([
            'proceso_id' => $procesoId,
            'usuario_id' => $usuarioId,
            'fecha_subida' => now(),
            'tipo' => $tipo,
            'path' => $path,
            'url' => null,
            'nombre_original' => $file->getClientOriginalName(),
            'tamanio' => $file->getSize(),
            'tipo_mime' => $file->getMimeType(),
            'is_publico' => false,
        ]);

        Log::info('[Informes] Archivo subido exitosamente', [
            'informe_archivo_id' => $archivo->informe_archivo_id,
            'nombre_original' => $archivo->nombre_original,
            'usuario_id' => $usuarioId,
            'proceso_id' => $procesoId,
        ]);

        return $archivo;
    }

    public function makePublic(ReportFile $archivo, ?\DateTime $expiresAt = null): ReportFile
    {
        $token = (string) Str::uuid();
        $expiresAt = $expiresAt ?? now()->addYear()->toDateTime();
        $expira = $expiresAt instanceof \DateTime ? $expiresAt->format('Y-m-d H:i:s') : $expiresAt;

        $archivo->update([
            'is_publico' => true,
            'token_publico' => $token,
            'link_expira_en' => $expira,
        ]);

        return $archivo->refresh();
    }

    public function revokePublicAccess(ReportFile $archivo): ReportFile
    {
        $archivo->update([
            'is_publico' => false,
            'token_publico' => null,
            'link_expira_en' => null,
        ]);

        return $archivo->refresh();
    }

    public function deleteFile(ReportFile $archivo): bool
    {
        if (($archivo->tipo ?? 'archivo') === 'archivo' && $archivo->path && Storage::disk($this->disk)->exists($archivo->path)) {
            Storage::disk($this->disk)->delete($archivo->path);
        }

        $archivo->delete();
        return true;
    }
}
