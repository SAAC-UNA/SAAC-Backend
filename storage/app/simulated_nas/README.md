# Carpeta de Simulación NAS

Esta carpeta simula el NAS de la universidad durante el desarrollo local.

## Estructura

```
simulated_nas/
├── evidencias/     # Archivos de evidencias subidos por los usuarios
├── documentos/     # Documentos del sistema
└── temp/           # Archivos temporales
```

## Uso en el Código

Usar el facade `Storage` sin especificar el disk:

```php
use Illuminate\Support\Facades\Storage;

// Guardar archivo
Storage::put('evidencias/archivo.pdf', $contenido);

// Verificar si existe
if (Storage::exists('evidencias/archivo.pdf')) {
    // ...
}

// Obtener archivo
$contenido = Storage::get('evidencias/archivo.pdf');

// Eliminar archivo
Storage::delete('evidencias/archivo.pdf');
```

## Cambio a Producción

Cuando se tenga acceso al NAS de la universidad:

1. Actualizar `.env`:
   ```env
   FILESYSTEM_DISK=production_nas
   NAS_HOST=10.0.0.50
   NAS_USERNAME=saac_user
   NAS_PASSWORD=***
   NAS_PORT=22
   NAS_ROOT=/mnt/data/saac
   ```

2. **No cambiar código** - Laravel manejará automáticamente el nuevo disk.

## Nota Importante

⚠️ Esta carpeta está en `.gitignore` - los archivos aquí NO se suben al repositorio.
