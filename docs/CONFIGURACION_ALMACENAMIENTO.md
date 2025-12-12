# Configuración del Sistema de Almacenamiento (NAS Simulado)

## 📋 Resumen

Se configuró un sistema de almacenamiento flexible que permite desarrollar localmente usando una carpeta simulada y luego cambiar al NAS de la universidad sin modificar código.

---

## 🎯 Objetivo

Debido a las limitaciones de recursos (2GB RAM) para simular un NAS completo, se implementó la **Opción 1: Laravel Local Disk Abstraction**, que permite:

- ✅ Desarrollar usando carpetas locales
- ✅ Cambiar al NAS de producción con solo modificar `.env`
- ✅ **Cero cambios en el código de la aplicación**
- ✅ Uso mínimo de recursos del sistema

---

## 🛠️ Configuración Implementada

### 1. Disks Configurados en `config/filesystems.php`

Se agregaron dos nuevos disks:

```php
'disks' => [
    // ... otros disks existentes ...

    // Disk de simulación NAS para desarrollo local
    'simulated_nas' => [
        'driver' => 'local',
        'root' => storage_path('app/simulated_nas'),
        'throw' => true,
    ],

    // Disk de producción para el NAS de la universidad (SFTP)
    'production_nas' => [
        'driver' => 'sftp',
        'host' => env('NAS_HOST'),
        'username' => env('NAS_USERNAME'),
        'password' => env('NAS_PASSWORD'),
        'port' => env('NAS_PORT', 22),
        'root' => env('NAS_ROOT', '/mnt/data/saac'),
        'timeout' => 30,
        'throw' => true,
    ],
],
```

### 2. Variables de Entorno en `.env`

**Para desarrollo (actual):**
```env
FILESYSTEM_DISK=simulated_nas
```

**Para producción (cuando esté disponible el NAS):**
```env
# Descomentar estas líneas y configurar con los datos reales:
# FILESYSTEM_DISK=production_nas
# NAS_HOST=10.0.0.50
# NAS_USERNAME=saac_user
# NAS_PASSWORD=secure_password
# NAS_PORT=22
# NAS_ROOT=/mnt/data/saac
```

### 3. Estructura de Carpetas

La carpeta `storage/app/simulated_nas/` ya está creada en el repositorio con la siguiente estructura:

```
storage/app/simulated_nas/
├── evidencias/     # Archivos de evidencias subidos por usuarios
├── documentos/     # Documentos del sistema
├── temp/           # Archivos temporales
├── .gitignore      # Ignora archivos (no se suben al repositorio)
└── README.md       # Documentación local
```

**✅ La estructura de carpetas está en git, pero los archivos dentro son ignorados automáticamente.**

Cuando clones el repositorio, las carpetas ya estarán creadas y listas para usar.

---

## 💻 Cómo Usar en el Código

### Uso del Storage Facade (sin especificar disk)

Laravel automáticamente usará el disk configurado en `FILESYSTEM_DISK`:

```php
use Illuminate\Support\Facades\Storage;

// Guardar un archivo
Storage::put('evidencias/proyecto_1.pdf', $contenidoPDF);

// Guardar con ruta completa
Storage::put('evidencias/carrera_1/ciclo_2024/proyecto_1.pdf', $contenidoPDF);

// Verificar si un archivo existe
if (Storage::exists('evidencias/proyecto_1.pdf')) {
    // El archivo existe
}

// Obtener contenido de un archivo
$contenido = Storage::get('evidencias/proyecto_1.pdf');

// Obtener URL pública (solo si aplica)
$url = Storage::url('documentos/guia.pdf');

// Eliminar un archivo
Storage::delete('evidencias/proyecto_1.pdf');

// Eliminar múltiples archivos
Storage::delete([
    'evidencias/archivo1.pdf',
    'evidencias/archivo2.pdf'
]);

// Listar archivos en una carpeta
$archivos = Storage::files('evidencias');

// Listar todos los archivos recursivamente
$todosLosArchivos = Storage::allFiles('evidencias');

// Copiar un archivo
Storage::copy('evidencias/original.pdf', 'evidencias/copia.pdf');

// Mover un archivo
Storage::move('temp/archivo.pdf', 'evidencias/archivo.pdf');

// Obtener tamaño del archivo
$tamaño = Storage::size('evidencias/proyecto_1.pdf');

// Obtener última modificación
$timestamp = Storage::lastModified('evidencias/proyecto_1.pdf');
```

### Subir Archivos desde Request

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

public function uploadEvidence(Request $request)
{
    $request->validate([
        'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
    ]);

    // Opción 1: Guardar con nombre automático
    $path = $request->file('archivo')->store('evidencias');
    
    // Opción 2: Guardar con nombre personalizado
    $nombreArchivo = time() . '_' . $request->file('archivo')->getClientOriginalName();
    $path = $request->file('archivo')->storeAs('evidencias', $nombreArchivo);
    
    // Opción 3: Guardar en subcarpeta organizada
    $carreraId = 1;
    $cicloId = 2024;
    $path = $request->file('archivo')->storeAs(
        "evidencias/carrera_{$carreraId}/ciclo_{$cicloId}",
        $nombreArchivo
    );

    return response()->json([
        'message' => 'Archivo subido exitosamente',
        'path' => $path
    ]);
}
```

### Descargar Archivos

```php
use Illuminate\Support\Facades\Storage;

public function downloadEvidence($filename)
{
    $path = "evidencias/{$filename}";
    
    if (!Storage::exists($path)) {
        abort(404, 'Archivo no encontrado');
    }

    return Storage::download($path);
    
    // O con nombre personalizado
    return Storage::download($path, 'mi_evidencia.pdf');
}
```

---

## 🔄 Proceso de Migración a Producción

### Paso 1: Obtener Credenciales del NAS

Contactar al departamento de TI de la universidad para obtener:
- Host/IP del servidor NAS
- Usuario y contraseña
- Puerto (generalmente 22 para SFTP)
- Ruta base en el NAS

### Paso 2: Actualizar `.env`

```env
# Cambiar el disk activo
FILESYSTEM_DISK=production_nas

# Configurar credenciales del NAS
NAS_HOST=nas.una.ac.cr
NAS_USERNAME=saac_user
NAS_PASSWORD=tu_password_seguro
NAS_PORT=22
NAS_ROOT=/mnt/data/saac
```

### Paso 3: Verificar Conexión

```bash
php artisan tinker
```

```php
// En tinker
Storage::put('test.txt', 'Conexión exitosa al NAS');
Storage::get('test.txt');
Storage::delete('test.txt');
```

### Paso 4: Migrar Archivos Existentes (Opcional)

Si ya tienes archivos en `simulated_nas`, puedes migrarlos:

```php
use Illuminate\Support\Facades\Storage;

// Copiar todos los archivos del simulated_nas al production_nas
$archivos = Storage::disk('simulated_nas')->allFiles();

foreach ($archivos as $archivo) {
    $contenido = Storage::disk('simulated_nas')->get($archivo);
    Storage::disk('production_nas')->put($archivo, $contenido);
}
```

---

## 🧪 Comandos de Prueba

### Verificar configuración actual

```bash
# Ver qué disk está activo
php artisan tinker --execute="echo config('filesystems.default');"
```

### Probar escritura y lectura

```bash
# Crear archivo de prueba
php artisan tinker --execute="Storage::put('test.txt', 'Hola Mundo'); echo Storage::get('test.txt');"
```

### Listar archivos

```bash
# Ver todos los archivos en evidencias
php artisan tinker --execute="print_r(Storage::files('evidencias'));"
```

---

## 📁 Estructura de Archivos Recomendada

Organizar los archivos por carrera y ciclo:

```
evidencias/
├── carrera_1/
│   ├── ciclo_2024/
│   │   ├── criterio_1/
│   │   │   ├── evidencia_1.pdf
│   │   │   └── evidencia_2.pdf
│   │   └── criterio_2/
│   └── ciclo_2025/
└── carrera_2/

documentos/
├── plantillas/
├── reportes/
└── manuales/

temp/
└── uploads_pendientes/
```

---

## 🔒 Consideraciones de Seguridad

### 1. Validación de Archivos

Siempre validar tipo y tamaño:

```php
$request->validate([
    'archivo' => [
        'required',
        'file',
        'mimes:pdf,doc,docx,xlsx,jpg,png',
        'max:10240', // 10MB
    ],
]);
```

### 2. Sanitizar Nombres de Archivos

```php
$nombreOriginal = $request->file('archivo')->getClientOriginalName();
$nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
$nombreFinal = time() . '_' . $nombreSeguro;
```

### 3. Controlar Acceso

```php
// Solo permitir descarga a usuarios autenticados y autorizados
public function download($id)
{
    $evidencia = Evidencia::findOrFail($id);
    
    // Verificar permisos
    if (!auth()->user()->can('view', $evidencia)) {
        abort(403);
    }
    
    return Storage::download($evidencia->ruta_archivo);
}
```

---

## 🆘 Troubleshooting

### Error: "Disk [simulated_nas] not configured"

**Solución:**
```bash
php artisan config:clear
php artisan cache:clear
```

### Error al conectar con el NAS de producción

**Verificar:**
1. Credenciales correctas en `.env`
2. Firewall no bloquea el puerto 22
3. Usuario tiene permisos en la carpeta del NAS

**Probar conexión SFTP manualmente:**
```bash
sftp -P 22 usuario@host
```

### Archivos no se suben

**Verificar permisos de carpetas:**
```bash
# Linux/Mac
chmod -R 775 storage/app/simulated_nas
chown -R www-data:www-data storage/app/simulated_nas

# Windows (ejecutar como administrador)
icacls "storage\app\simulated_nas" /grant Users:F /T
```

### Migrar de simulated_nas a production_nas

**Script de migración:**

```php
// Crear en routes/console.php o comando Artisan
Artisan::command('storage:migrate-to-nas', function () {
    $this->info('Iniciando migración a NAS de producción...');
    
    $archivos = Storage::disk('simulated_nas')->allFiles();
    $total = count($archivos);
    $migrados = 0;
    
    foreach ($archivos as $archivo) {
        try {
            $contenido = Storage::disk('simulated_nas')->get($archivo);
            Storage::disk('production_nas')->put($archivo, $contenido);
            $migrados++;
            $this->info("✓ {$archivo} ({$migrados}/{$total})");
        } catch (\Exception $e) {
            $this->error("✗ Error en {$archivo}: {$e->getMessage()}");
        }
    }
    
    $this->info("Migración completada: {$migrados}/{$total} archivos");
});
```

---

## 📝 Notas Importantes

1. **No hardcodear rutas:** Siempre usar `Storage::` para que funcione en desarrollo y producción
2. **Usar FILESYSTEM_DISK:** Laravel lee esta variable automáticamente
3. **Estructura consistente:** Mantener la misma organización de carpetas en simulated_nas y production_nas
4. **Backups:** El NAS de la universidad debería tener sistema de respaldo, pero confirmar con TI
5. **Rendimiento:** El NAS remoto puede ser más lento que local, considerar cache para archivos frecuentes

---

## 🚀 Configuración Inicial (Para Nuevos Miembros del Equipo)

Si eres nuevo en el proyecto:

✅ **Las carpetas ya están creadas** - Al clonar el repositorio, la estructura de `simulated_nas` estará lista.

Solo necesitas verificar que funciona:

```bash
# Verificar que el almacenamiento funciona
php artisan tinker --execute="
    Storage::put('test.txt', 'Sistema de almacenamiento configurado');
    echo Storage::get('test.txt');
    Storage::delete('test.txt');
"
```

**No necesitas crear ninguna carpeta manualmente.** 🎉

---

**Fecha de configuración:** Noviembre 1, 2025  
**Configurado por:** José Jara  
**Versión de Laravel:** 11.x  
**Estado:** ✅ Funcionando en desarrollo local
