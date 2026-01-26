# 🧪 GUÍA DE PRUEBAS RF-15 - Sistema de Solicitudes de Ampliación

## 📋 Requisitos Previos

1. **MySQL** corriendo en puerto 3307
2. **Redis** corriendo (para sesiones)
3. **OpenLDAP** corriendo en puerto 389 (para autenticación)

## 🚀 Iniciar Servidor

Abre una terminal PowerShell y ejecuta:

```powershell
cd C:\SAAC-UNA\SAAC-Backend
php artisan serve
```

Deja esta terminal abierta (el servidor debe seguir corriendo).

## ✅ Ejecutar Pruebas

### Opción 1: Script Automático

Abre OTRA terminal PowerShell diferente y ejecuta:

```powershell
cd C:\SAAC-UNA\SAAC-Backend
.\PRUEBAS_RF15.ps1
```

Este script ejecutará todos los pasos automáticamente.

### Opción 2: Pruebas Manuales (Postman/cURL)

Sigue estos pasos uno por uno:

#### 1️⃣ LOGIN - Obtener Tokens

**José (Profesor)**
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" `
  -Method POST `
  -Body '{"cedula":"208330811","password":"password123"}' `
  -ContentType "application/json"
```
Copia el `token` de la respuesta.

**Admin**
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" `
  -Method POST `
  -Body '{"cedula":"203948609","password":"password123"}' `
  -ContentType "application/json"
```

#### 2️⃣ LISTAR SOLICITUDES - Verificar Filtro por Rol

**José ve SOLO sus solicitudes:**
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" `
  -Headers @{"Authorization"="Bearer TU_TOKEN_JOSE_AQUI"}
```

**Admin ve TODAS:**
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" `
  -Headers @{"Authorization"="Bearer TU_TOKEN_ADMIN_AQUI"}
```

#### 3️⃣ CREAR SOLICITUD

```powershell
$body = @{
  evidencia_asignacion_id = 1
  motivo = "Solicito ampliación por carga académica"
  fecha_sugerida = "2026-06-30"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" `
  -Method POST `
  -Body $body `
  -Headers @{
    "Authorization"="Bearer TU_TOKEN_JOSE_AQUI"
    "Content-Type"="application/json"
  }
```

#### 4️⃣ VALIDACIÓN DUPLICADOS - Intentar Crear Duplicado

```powershell
# José ya tiene solicitud PENDIENTE para evidencia 6
# Intentar crear otra debe dar error 422

$duplicado = @{
  evidencia_asignacion_id = 6
  motivo = "Intento duplicado"
  fecha_sugerida = "2026-07-15"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" `
  -Method POST `
  -Body $duplicado `
  -Headers @{
    "Authorization"="Bearer TU_TOKEN_JOSE_AQUI"
    "Content-Type"="application/json"
  }
```

**Resultado esperado:** Error 422 - "Ya tiene solicitud PENDIENTE para esta evidencia"

#### 5️⃣ VER DETALLE

```powershell
# Reemplaza ID_SOLICITUD con un ID real
Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/ID_SOLICITUD" `
  -Headers @{"Authorization"="Bearer TU_TOKEN_JOSE_AQUI"}
```

#### 6️⃣ ACTUALIZAR (Solo PENDIENTES)

```powershell
$update = @{
  motivo = "Motivo actualizado"
  fecha_sugerida = "2026-08-15"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/ID_SOLICITUD" `
  -Method PUT `
  -Body $update `
  -Headers @{
    "Authorization"="Bearer TU_TOKEN_JOSE_AQUI"
    "Content-Type"="application/json"
  }
```

#### 7️⃣ ELIMINAR (Solo PENDIENTES)

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/ID_SOLICITUD" `
  -Method DELETE `
  -Headers @{"Authorization"="Bearer TU_TOKEN_JOSE_AQUI"}
```

#### 8️⃣ EVIDENCIAS PRÓXIMAS A VENCER

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer" `
  -Headers @{"Authorization"="Bearer TU_TOKEN_JOSE_AQUI"}
```

## 👥 Usuarios de Prueba

| Cédula     | Password     | Rol           | User ID |
|------------|--------------|---------------|---------|| 203849675  | password123  | SuperUsuario  | 2       || 208330811  | password123  | Profesor      | 4       |
| 203948609  | password123  | Administrador | 1       |
| 117540925  | password123  | Encargado     | 3       |

## 🔍 Casos de Prueba Importantes

### ✅ Caso 1: Filtro por Rol
- **José (Profesor)** debe ver SOLO 1 solicitud (la suya, ID 10)
- **Admin** debe ver TODAS (5 solicitudes de diferentes usuarios)

### ✅ Caso 2: Validación Duplicados
- José tiene solicitud PENDIENTE para evidencia_asignacion_id=6
- Intentar crear otra para evidencia 6 → **Error 422**
- Crear para evidencia diferente → **OK 201**

### ✅ Caso 3: Estados
- Actualizar/Eliminar solicitud **PENDIENTE** → OK
- Actualizar/Eliminar solicitud **Aprobada/Rechazada** → Error 422

## 📊 Verificar Resultados

### En Logs
```powershell
Get-Content storage/logs/laravel.log -Tail 50
```

### En Base de Datos
```powershell
php artisan tinker --execute="DB::table('SOLICITUD_AMPLIACION')->get()"
```

## 🎯 Qué Debe Funcionar

- [x] Login con LDAP + token Sanctum
- [x] Profesor ve solo SUS solicitudes
- [x] Admin/Encargado ven TODAS
- [x] Crear solicitud (POST)
- [x] Rechazar duplicados PENDIENTES (422)
- [x] Ver detalle (GET /id)
- [x] Actualizar PENDIENTE (PUT /id)
- [x] Eliminar PENDIENTE (DELETE /id)
- [x] Evidencias próximas a vencer

## 📝 Documentación Completa

Ver archivo: `docs/RF15_SEGURIDAD_AUTENTICACION.md`

## 🐛 Troubleshooting

**Error "No es posible conectar":**
- Verifica que `php artisan serve` esté corriendo
- URL debe ser `http://localhost:8000` (no 127.0.0.1)

**Error 401 "No autenticado":**
- Token expiró (24h), hacer login de nuevo
- Verificar header: `Authorization: Bearer TOKEN_AQUI`

**Error 422 "Evidencia no existe":**
- Verificar que `evidencia_asignacion_id` existe en BD
- Verificar que esté asignada al usuario autenticado

**Error 500:**
- Revisar logs: `storage/logs/laravel.log`
- Ejecutar: `php artisan cache:clear`
