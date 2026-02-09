# 🎯 Guía de Pruebas POSTMAN - Módulo de Roles

## 📋 Requisitos Funcionales

**RF-Roles**: Como Superusuario/Administrador, quiero poder gestionar roles personalizados, definiendo nombre, descripción y privilegios por módulo para adaptar permisos del sistema.

---

## 🔐 Configuración Inicial

### 1. Crear Environment en Postman

```
Variable: base_url
Valor: http://localhost:8000/api

Variable: token
Valor: (se llenará automáticamente después del login)
```

### 2. Usuarios de Prueba

| Usuario | Cédula | Contraseña | Rol | Acceso a Roles |
|---------|--------|------------|-----|----------------|
| **Pablo Castillo** | 203849675 | password123 | Superusuario | ✅ SÍ |
| **Admin** | 203948609 | password123 | Administrador | ✅ SÍ |
| **José Jara** | 208330811 | password123 | Profesor | ❌ NO (403) |
| **Naydelin** | 801490957 | password123 | Estudiante | ❌ NO (403) |

---

## 🧪 PASO A PASO - PRUEBAS EN POSTMAN

### ✅ PASO 1: Autenticación

**Endpoint**: `POST {{base_url}}/auth/login`

**Headers**:
```
Content-Type: application/json
Accept: application/json
```

**Body** (raw JSON):
```json
{
    "cedula": "203849675",
    "password": "password123"
}
```

**Script en Tests** (para guardar el token):
```javascript
pm.environment.set("token", pm.response.json().token);
```

**✅ Resultado Esperado**: 
- Status: `200 OK`
- Se guarda el token automáticamente
- Respuesta incluye: `token`, `user`, `roles`

---

### ✅ PASO 2: Listar Todos los Roles

**Endpoint**: `GET {{base_url}}/roles`

**Headers**:
```
Authorization: Bearer {{token}}
Accept: application/json
```

**✅ Resultado Esperado**:
- Status: `200 OK`
- JSON con array de roles:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Superusuario",
            "description": "...",
            "permissions": [...]
        },
        // ... más roles
    ]
}
```

**❌ Prueba de Seguridad**:
- Sin token → `401 Unauthorized`
- Con rol "Profesor" → `403 Forbidden`

---

### ✅ PASO 3: Ver Detalle de un Rol

**Endpoint**: `GET {{base_url}}/roles/1`

**Headers**:
```
Authorization: Bearer {{token}}
Accept: application/json
```

**✅ Resultado Esperado**:
- Status: `200 OK`
- JSON con detalles del rol:
```json
{
    "data": {
        "id": 1,
        "name": "Superusuario",
        "description": "Control total del sistema",
        "permissions": [
            "gestionar usuarios",
            "gestionar roles",
            // ...
        ]
    }
}
```

**❌ Prueba de Error**:
- ID inexistente (99999) → `404 Not Found`

---

### ✅ PASO 4: Crear Nuevo Rol

**Endpoint**: `POST {{base_url}}/roles`

**Headers**:
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

**Body** (raw JSON):
```json
{
    "name": "Coordinador de Evidencias",
    "description": "Rol encargado de coordinar la gestión de evidencias",
    "permissions": [
        "ver evidencias",
        "crear evidencias",
        "editar evidencias",
        "ver reportes"
    ]
}
```

**✅ Resultado Esperado**:
- Status: `201 Created`
- Mensaje: "Rol creado con éxito"
- Incluye datos del rol creado

**🔍 Verificar Bitácora**:
```
GET {{base_url}}/bitacora
Buscar: "Rol creado: Coordinador de Evidencias"
Módulo: "Roles"
Acción: "crear"
```

**❌ Validaciones**:
- Sin nombre → `422 Unprocessable Entity`
- Nombre duplicado → `422 Unprocessable Entity`

---

### ✅ PASO 5: Actualizar Rol

**Endpoint**: `PUT {{base_url}}/roles/8`  
_(Usar el ID del rol recién creado)_

**Headers**:
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

**Body** (raw JSON):
```json
{
    "name": "Coordinador de Evidencias",
    "description": "Rol actualizado - Coordina evidencias y reportes",
    "permissions": [
        "ver evidencias",
        "crear evidencias",
        "editar evidencias",
        "eliminar evidencias",
        "ver reportes",
        "generar reportes"
    ]
}
```

**✅ Resultado Esperado**:
- Status: `200 OK`
- Mensaje: "Rol actualizado con éxito"

**🔍 Verificar Bitácora**:
```
GET {{base_url}}/bitacora
Buscar: "Rol actualizado: Coordinador de Evidencias"
Debe mostrar CAMBIOS DETECTADOS (permisos agregados)
```

**❌ Validaciones**:
- Rol inexistente → `404 Not Found`

---

### ✅ PASO 6: Listar Permisos Disponibles

**Endpoint**: `GET {{base_url}}/roles/permisos`

**Headers**:
```
Authorization: Bearer {{token}}
Accept: application/json
```

**✅ Resultado Esperado**:
- Status: `200 OK`
- Lista de todos los permisos disponibles:
```json
{
    "data": [
        {
            "id": 1,
            "name": "gestionar usuarios",
            "description": "..."
        },
        {
            "id": 2,
            "name": "gestionar roles",
            "description": "..."
        }
        // ... más permisos
    ]
}
```

---

### ✅ PASO 7: Eliminar Rol

**Endpoint**: `DELETE {{base_url}}/roles/8`  
_(Usar el ID del rol de prueba)_

**Headers**:
```
Authorization: Bearer {{token}}
Accept: application/json
```

**✅ Resultado Esperado**:
- Status: `200 OK`
- Mensaje: "Rol eliminado con éxito"

**🔍 Verificar Bitácora**:
```
GET {{base_url}}/bitacora
Buscar: "Rol eliminado: Coordinador de Evidencias"
```

**❌ Validaciones**:
- Rol inexistente → `404 Not Found`
- No se puede eliminar rol con usuarios asignados

---

## 🧪 PRUEBAS DE SEGURIDAD

### 🔒 Prueba 1: Sin Autenticación

```
GET {{base_url}}/roles
(Sin header Authorization)

❌ Esperado: 401 Unauthorized
```

### 🔒 Prueba 2: Token Inválido

```
GET {{base_url}}/roles
Authorization: Bearer token_invalido_12345

❌ Esperado: 401 Unauthorized
```

### 🔒 Prueba 3: Usuario sin Permisos

```
1. Login con José (Profesor): cedula=208330811
2. GET {{base_url}}/roles

❌ Esperado: 403 Forbidden
{
    "message": "Acceso denegado. Rol no autorizado."
}
```

---

## 📊 VERIFICAR BITÁCORA

Después de cada operación (crear/actualizar/eliminar), verificar:

**Endpoint**: `GET {{base_url}}/bitacora?modulo=Roles&per_page=20`

**Campos a verificar**:
- ✅ `usuario_id`: ID del usuario que hizo la acción
- ✅ `modulo`: "Roles"
- ✅ `tipo_accion`: "crear", "actualizar", "eliminar"
- ✅ `detalle`: Descripción de la acción
- ✅ `fecha_hora`: Timestamp de la acción

---

## 🎯 CHECKLIST DE PRUEBAS

- [ ] Login exitoso y token guardado
- [ ] Listar roles con Superusuario
- [ ] Listar roles con Administrador
- [ ] Intento de acceso con Profesor (403)
- [ ] Intento sin token (401)
- [ ] Ver detalle de rol existente
- [ ] Ver detalle de rol inexistente (404)
- [ ] Crear rol con datos válidos
- [ ] Verificar registro en bitácora (crear)
- [ ] Crear rol sin nombre (422)
- [ ] Crear rol con nombre duplicado (422)
- [ ] Actualizar rol existente
- [ ] Verificar registro en bitácora (actualizar)
- [ ] Actualizar rol inexistente (404)
- [ ] Listar permisos disponibles
- [ ] Eliminar rol
- [ ] Verificar registro en bitácora (eliminar)
- [ ] Eliminar rol inexistente (404)

---

## 🚀 Script de Limpieza

Si necesitas limpiar los roles de prueba:

```bash
# PowerShell
cd C:\SAAC-UNA\SAAC-Backend
php artisan tinker

# En tinker:
$role = \App\Models\Role::where('name', 'Coordinador de Evidencias')->first();
if ($role) {
    $role->delete();
    echo "Rol eliminado\n";
}
```

---

## 📝 Notas Importantes

1. **Roles protegidos**: No se pueden eliminar: Superusuario, Administrador, Profesor, Encargado
2. **Permisos**: Validar que los permisos existen en la tabla `permissions`
3. **Bitácora**: Cada operación DEBE registrarse automáticamente
4. **Rate Limiting**: Las rutas tienen límite de 60 peticiones/minuto

---

## 🐛 Troubleshooting

**Error: 401 Unauthorized**
- Verificar que el token esté en el header
- Verificar que el token sea válido
- Re-login si el token expiró

**Error: 403 Forbidden**
- Verificar que el usuario sea Superusuario o Administrador
- Revisar: `php artisan route:list | Select-String roles`

**Error: 500 Internal Server Error**
- Revisar logs: `storage/logs/laravel.log`
- Verificar base de datos activa
- Ejecutar: `php artisan config:clear`
