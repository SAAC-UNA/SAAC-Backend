# Documentación API - Sistema de Archivos (HU-008)

## 📋 Índice
1. [Descripción General](#descripción-general)
2. [Endpoints Disponibles](#endpoints-disponibles)
3. [Modelos y Estructura](#modelos-y-estructura)
4. [Seguridad y Validaciones](#seguridad-y-validaciones)
5. [Guía de Implementación Frontend](#guía-de-implementación-frontend)
6. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Descripción General

Sistema completo de gestión de archivos para evidencias del proceso de acreditación. Permite subir, listar, eliminar archivos y generar enlaces públicos para el ente acreditador (SINAES).

### Características principales:
- ✅ Subida de archivos hasta 50MB
- ✅ Validación de formatos permitidos
- ✅ Almacenamiento con nombres UUID (seguridad)
- ✅ Generación de enlaces públicos con expiración
- ✅ Asociación a evidencias, usuarios y procesos
- ✅ Control de acceso mediante FilePolicy

---

## Endpoints Disponibles

### Base URL
```
http://127.0.0.1:8000/api/archivos
```

### 1. Listar Archivos
**GET** `/archivos?evidencia_id={id}`  
**GET** `/archivos?proceso_id={id}`

**Descripción:** Obtiene la lista de archivos filtrados por evidencia o proceso.

**Query Parameters:**
- `evidencia_id` (opcional): ID de la evidencia
- `proceso_id` (opcional): ID del proceso

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "archivo_id": 1,
      "nombre_original": "Plan Estratégico.pdf",
      "fecha_subida": "2025-11-24 20:42:27",
      "is_publico": false,
      "url_publica": null,
      "evidencia_id": 11,
      "usuario_id": 2,
      "proceso_id": 1
    }
  ]
}
```

---

### 2. Subir Archivo
**POST** `/archivos`

**Descripción:** Sube un nuevo archivo asociado a una evidencia.

**Content-Type:** `multipart/form-data`

**Body Parameters:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `archivo` | File | Sí | Archivo a subir (max 50MB) |
| `evidencia_id` | Integer | Sí | ID de la evidencia asociada |
| `proceso_id` | Integer | Sí | ID del proceso asociado |
| `usuario_id` | Integer | Sí (temporal) | ID del usuario que sube* |

> **NOTA:** `usuario_id` es temporal para pruebas. Cuando se implemente autenticación (HU-001), se debe cambiar a `auth()->id()` en el backend.

**Formatos Permitidos:**
- Documentos: `pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, rtf`
- Imágenes: `jpg, jpeg, png, webp`
- Videos: `mp4, avi, mov, wmv, mkv, webm`
- Comprimidos: `zip, rar, 7z`

**Response Exitoso (201):**
```json
{
  "success": true,
  "message": "Archivo subido exitosamente.",
  "data": {
    "archivo_id": 1,
    "nombre_original": "Evidencia.pdf",
    "fecha_subida": "2025-11-24 20:42:27",
    "is_publico": false,
    "evidencia_id": 11,
    "usuario_id": 2,
    "proceso_id": 1
  }
}
```

**Response Error (422):**
```json
{
  "message": "El formato del archivo no está permitido. Formatos válidos: PDF, DOC, DOCX...",
  "errors": {
    "archivo": [
      "El archivo no debe superar los 50MB."
    ]
  }
}
```

---

### 3. Ver Metadata de Archivo
**GET** `/archivos/{archivo_id}`

**Descripción:** Obtiene los metadatos de un archivo específico.

**Response:**
```json
{
  "success": true,
  "data": {
    "archivo_id": 1,
    "nombre_original": "Evidencia.pdf",
    "fecha_subida": "2025-11-24 20:42:27",
    "is_publico": false,
    "evidencia": {
      "evidencia_id": 11,
      "nombre": "Plan Estratégico"
    },
    "usuario": {
      "usuario_id": 2,
      "nombre_completo": "Juan Pérez",
      "email": "juan@una.cr"
    },
    "proceso": {
      "proceso_id": 1,
      "nombre": "Autoevaluación 2025"
    }
  }
}
```

---

### 4. Eliminar Archivo
**DELETE** `/archivos/{archivo_id}`

**Descripción:** Elimina un archivo del sistema (físico y registro BD).

**Autorización:** Solo el usuario que subió el archivo o administradores.

**Response:**
```json
{
  "success": true,
  "message": "Archivo eliminado exitosamente."
}
```

---

### 5. Hacer Archivo Público
**POST** `/archivos/{archivo_id}/make-public`

**Descripción:** Genera un enlace público UUID para acceder al archivo sin autenticación (para SINAES).

**Autorización:** Solo usuarios con rol: Superusuario, Vicerrectoría de Docencia, o Administrador.

**Body (opcional):**
```json
{
  "expires_at": "2026-11-24 23:59:59"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Archivo marcado como público exitosamente.",
  "data": {
    "archivo_id": 1,
    "nombre_original": "Evidencia.pdf",
    "is_publico": true,
    "url_publica": "http://127.0.0.1:8000/api/p/a3f2bc4d-1234-5678-9abc-def123456789",
    "link_expira_en": "2026-11-24 20:42:27"
  }
}
```

---

### 6. Revocar Acceso Público
**POST** `/archivos/{archivo_id}/revoke-public`

**Descripción:** Revoca el acceso público de un archivo.

**Autorización:** Solo usuarios con rol: Superusuario, Vicerrectoría de Docencia, o Administrador.

**Response:**
```json
{
  "success": true,
  "message": "Acceso público revocado exitosamente.",
  "data": {
    "archivo_id": 1,
    "is_publico": false,
    "url_publica": null
  }
}
```

---

### 7. Operación Masiva - Hacer Públicos
**POST** `/archivos/bulk-make-public`

**Descripción:** Marca múltiples archivos como públicos simultáneamente.

**Autorización:** Solo usuarios con rol: Superusuario, Vicerrectoría de Docencia, o Administrador.

**Body:**
```json
{
  "archivos_ids": [1, 2, 3, 4, 5],
  "expires_at": "2026-11-24 23:59:59"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Archivos marcados como públicos exitosamente.",
  "count": 5,
  "data": [
    { "archivo_id": 1, "url_publica": "..." },
    { "archivo_id": 2, "url_publica": "..." }
  ]
}
```

---

### 8. Obtener Datos de Prueba (TEMPORAL)
**GET** `/archivos/test-data`

**Descripción:** Endpoint temporal para obtener usuarios, evidencias y procesos disponibles.

> **NOTA:** Este endpoint debe ser REMOVIDO en producción. Es solo para desarrollo/testing.

**Response:**
```json
{
  "success": true,
  "data": {
    "usuarios": [...],
    "evidencias": [...],
    "procesos": [...]
  }
}
```

---

## Modelos y Estructura

### Tabla ARCHIVO

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `archivo_id` | BIGINT (PK) | Identificador único |
| `evidencia_id` | BIGINT (FK) | ID de la evidencia asociada |
| `usuario_id` | BIGINT (FK) | ID del usuario que subió |
| `proceso_id` | BIGINT (FK) | ID del proceso asociado |
| `fecha_subida` | TIMESTAMP | Fecha y hora de subida |
| `path` | VARCHAR(512) | Ruta UUID del archivo en NAS |
| `nombre_original` | VARCHAR(255) | Nombre original del archivo |
| `is_publico` | BOOLEAN | Si el archivo es público (default: false) |
| `token_publico` | VARCHAR(36) UNIQUE | UUID para acceso público |
| `link_expira_en` | TIMESTAMP | Fecha de expiración del enlace |
| `created_at` | TIMESTAMP | Timestamp de creación |
| `updated_at` | TIMESTAMP | Timestamp de última actualización |

### Relaciones
- **ARCHIVO** → **EVIDENCIA** (Many-to-One)
- **ARCHIVO** → **USUARIO** (Many-to-One)
- **ARCHIVO** → **PROCESO** (Many-to-One)

---

## Seguridad y Validaciones

### Backend (Automático)

#### 1. Validación de Archivos
- **Tamaño máximo:** 50MB (51,200 KB)
- **Formatos permitidos:** Ver lista en endpoint de subida
- **MIME type validation:** Se valida en backend con `finfo`
- **Nombres UUID:** Todos los archivos se guardan con UUID para evitar colisiones y path traversal

#### 2. Autorización (FilePolicy)

| Acción | Permiso |
|--------|---------|
| `upload` | Usuario con asignación a la evidencia |
| `download` | Usuario asignado, owner, o archivo público |
| `view` | Usuario asignado, owner, o archivo público |
| `delete` | Usuario owner o Administrador/Superusuario |
| `makePublic` | Vicerrectoría/Administrador/Superusuario |
| `revokePublic` | Vicerrectoría/Administrador/Superusuario |
| `bulkMakePublic` | Vicerrectoría/Administrador/Superusuario |

#### 3. Seguridad de Almacenamiento
- **Path traversal protection:** Nombres UUID
- **Sanitización:** Nombres de archivo sanitizados
- **Disco separado:** `simulated_nas` (desarrollo) / `production_nas` (producción)
- **Logging:** Todas las operaciones se registran

### Frontend (A Implementar)

#### ⚠️ IMPORTANTE: Validación Cliente

**El frontend DEBE validar ANTES de enviar al backend:**

1. **Tamaño del archivo:**
```typescript
if (file.size > 50 * 1024 * 1024) {
  alert('El archivo excede el tamaño máximo de 50MB');
  return;
}
```

2. **Formato del archivo:**
```typescript
const allowedExtensions = [
  'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
  'jpg', 'jpeg', 'png', 'webp',
  'mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm',
  'zip', 'rar', '7z', 'txt', 'csv', 'rtf'
];

const extension = file.name.split('.').pop()?.toLowerCase();
if (!extension || !allowedExtensions.includes(extension)) {
  alert('Formato no permitido');
  return;
}
```

**Razón:** Evitar enviar archivos grandes que serán rechazados, ahorrando ancho de banda y tiempo.

---

## Guía de Implementación Frontend

### Componente de Subida de Archivos

#### Requisitos Mínimos del UI:

1. **Selección de Archivo:**
   - ✅ Botón para abrir explorador de archivos
   - ✅ Área de drag & drop (arrastrar archivo)
   - ✅ Preview del archivo seleccionado (nombre + tamaño)

2. **Formulario:**
   - ✅ Selector de Evidencia (dropdown con evidencias disponibles)
   - ✅ Selector de Proceso (dropdown con procesos disponibles)
   - ✅ Selector de Usuario (TEMPORAL - remover cuando haya auth)

3. **Feedback Visual:**
   - ✅ Barra de progreso durante la subida
   - ✅ Mensaje de éxito con datos del archivo subido
   - ✅ Mensajes de error claros (validación, servidor)
   - ✅ Estado de carga (deshabilitar botón mientras sube)

4. **Validaciones:**
   - ✅ Validar tamaño ANTES de enviar
   - ✅ Validar formato ANTES de enviar
   - ✅ Validar campos requeridos

### Ejemplo de Implementación React/TypeScript

```typescript
import { useState } from 'react';
import axios from 'axios';

const FileUpload = () => {
  const [file, setFile] = useState<File | null>(null);
  const [evidenciaId, setEvidenciaId] = useState('');
  const [procesoId, setProcesoId] = useState('');
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0];
    if (!selectedFile) return;

    // Validar tamaño
    if (selectedFile.size > 50 * 1024 * 1024) {
      alert('El archivo excede 50MB');
      return;
    }

    // Validar formato
    const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'png', /* ... */];
    const ext = selectedFile.name.split('.').pop()?.toLowerCase();
    if (!ext || !allowedExtensions.includes(ext)) {
      alert('Formato no permitido');
      return;
    }

    setFile(selectedFile);
  };

  const handleUpload = async () => {
    if (!file || !evidenciaId || !procesoId) {
      alert('Complete todos los campos');
      return;
    }

    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('evidencia_id', evidenciaId);
    formData.append('proceso_id', procesoId);
    formData.append('usuario_id', '1'); // TEMPORAL - usar auth después

    try {
      setUploading(true);
      const response = await axios.post('/api/archivos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round(
            (progressEvent.loaded * 100) / (progressEvent.total || 1)
          );
          setProgress(percentCompleted);
        },
      });

      alert('Archivo subido exitosamente: ' + response.data.data.archivo_id);
      setFile(null);
      setProgress(0);
    } catch (error: any) {
      alert('Error: ' + (error.response?.data?.message || 'Error desconocido'));
    } finally {
      setUploading(false);
    }
  };

  return (
    <div>
      {/* Área de drag & drop */}
      <div
        onDrop={(e) => {
          e.preventDefault();
          const droppedFile = e.dataTransfer.files[0];
          if (droppedFile) handleFileChange({ target: { files: [droppedFile] } } as any);
        }}
        onDragOver={(e) => e.preventDefault()}
        style={{ border: '2px dashed #ccc', padding: '20px', textAlign: 'center' }}
      >
        <input type="file" onChange={handleFileChange} style={{ display: 'none' }} id="fileInput" />
        <label htmlFor="fileInput" style={{ cursor: 'pointer' }}>
          {file ? `📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)` : '📂 Clic o arrastra un archivo aquí'}
        </label>
      </div>

      {/* Selectores */}
      <select value={evidenciaId} onChange={(e) => setEvidenciaId(e.target.value)}>
        <option value="">Seleccionar Evidencia</option>
        {/* Cargar opciones dinámicamente */}
      </select>

      <select value={procesoId} onChange={(e) => setProcesoId(e.target.value)}>
        <option value="">Seleccionar Proceso</option>
        {/* Cargar opciones dinámicamente */}
      </select>

      {/* Barra de progreso */}
      {uploading && (
        <div>
          <progress value={progress} max="100" />
          <span>{progress}%</span>
        </div>
      )}

      {/* Botón de subida */}
      <button onClick={handleUpload} disabled={uploading || !file}>
        {uploading ? 'Subiendo...' : 'Subir Archivo'}
      </button>
    </div>
  );
};

export default FileUpload;
```

---

## Ejemplos de Uso

### Ejemplo 1: Subir un PDF desde Postman

**Request:**
```
POST http://127.0.0.1:8000/api/archivos
Content-Type: multipart/form-data

archivo: [Archivo PDF]
evidencia_id: 11
proceso_id: 1
usuario_id: 2
```

**Response:**
```json
{
  "success": true,
  "message": "Archivo subido exitosamente.",
  "data": {
    "archivo_id": 1,
    "nombre_original": "Plan_Estrategico.pdf",
    "fecha_subida": "2025-11-24 20:42:27"
  }
}
```

---

### Ejemplo 2: Hacer archivo público y obtener URL

**Request:**
```
POST http://127.0.0.1:8000/api/archivos/1/make-public
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url_publica": "http://127.0.0.1:8000/api/p/a3f2bc4d-1234-5678-9abc-def123456789",
    "link_expira_en": "2026-11-24 20:42:27"
  }
}
```

---

### Ejemplo 3: Listar archivos de una evidencia

**Request:**
```
GET http://127.0.0.1:8000/api/archivos?evidencia_id=11
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "archivo_id": 1,
      "nombre_original": "Plan_Estrategico.pdf",
      "fecha_subida": "2025-11-24 20:42:27",
      "is_publico": true,
      "url_publica": "http://127.0.0.1:8000/api/p/..."
    },
    {
      "archivo_id": 2,
      "nombre_original": "Informe_Resultados.xlsx",
      "fecha_subida": "2025-11-24 21:15:33",
      "is_publico": false
    }
  ]
}
```

---

## 🔮 Próximos Pasos (Futuro)

### Para implementar cuando se programe el serving de archivos:

1. **Endpoints de Descarga/Visualización:**
   - `GET /archivos/{archivo_id}/download` - Descarga autenticada
   - `GET /archivos/{archivo_id}/view` - Vista inline autenticada
   - `GET /p/{token}` - Acceso público sin auth (SINAES)

2. **Métodos en FileController:**
   - Descomentar los métodos `download()`, `view()`, `publicAccess()`
   - Implementar lógica con `Storage::response()` y `Storage::download()`

3. **Rutas:**
   - Descomentar las rutas comentadas en `routes/api.php`
   - Configurar middleware correcto (auth vs público)

4. **Seguridad Adicional:**
   - Rate limiting en rutas públicas
   - Validación MIME con finfo
   - Headers de seguridad (X-Content-Type-Options, etc.)

---

## 📚 Referencias

- **Migración:** `database/migrations/2025_09_21_151941_create_files_table.php`
- **Modelo:** `app/Models/File.php`
- **Controller:** `app/Http/Controllers/FileController.php`
- **Service:** `app/Services/FileService.php`
- **Policy:** `app/Policies/FilePolicy.php`
- **Request:** `app/Http/Requests/StoreFileRequest.php`
- **Resource:** `app/Http/Resources/FileResource.php`
- **Rutas:** `routes/api.php` (líneas 62-96)
- **Config Red:** `CONFIGURACION_RED.md`

---

## ⚠️ Notas Importantes para el Frontend

1. **SIEMPRE validar tamaño y formato ANTES de enviar** - Ahorra ancho de banda
2. **Usar FormData para multipart/form-data** - No JSON
3. **Implementar barra de progreso** - Archivos grandes tardan
4. **Manejar errores 422** - Mostrar mensajes de validación claros
5. **El campo `usuario_id` es TEMPORAL** - Remover cuando haya auth
6. **Drag & Drop es OBLIGATORIO** - Mejor UX para usuarios
7. **Preview del archivo** - Mostrar nombre y tamaño antes de subir
8. **Deshabilitar botón durante upload** - Evitar duplicados
9. **Limpiar formulario después de éxito** - Reset file input
10. **Timeout aumentado en axios** - Configurar al menos 60 segundos

---

**Documentación generada:** 2025-11-24  
**Versión:** HU-008 - Subida de Evidencias  
**Autor:** Sistema SAAC - UNA
