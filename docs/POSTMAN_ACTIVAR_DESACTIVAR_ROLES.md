# 📡 POSTMAN - Activar/Desactivar Roles

## 🔐 Autenticación Previa

Todos los endpoints requieren autenticación. Primero obtén el token:

### **1. Login**
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "cedula": "207800171",
  "password": "password123"
}
```

**Respuesta:**
```json
{
  "token": "1|abcd1234...",
  "user": {
    "usuario_id": 4,
    "nombre": "Ian Enmanuel Villegas Jimenez",
    "cedula": "207800171",
    "email": "ian.villegas@est.una.ac.cr",
    "status": "active",
    "roles": ["Encargado de Acreditación"]
  }
}
```

**Usa el token en todos los siguientes requests:**
```
Authorization: Bearer 1|abcd1234...
```

---

## 📋 Listar Roles (verificar estado actual)

### **2. GET /api/roles**
```http
GET http://localhost:8000/api/roles
Authorization: Bearer {{token}}
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Superusuario",
      "description": "Control total del sistema",
      "status": "activo",  ← Estado del rol
      "permissions": [...],
      "creadoEl": "2026-04-18 14:45:12",
      "actualizadoEl": "2026-04-18 14:45:12"
    },
    {
      "id": 4,
      "name": "Asistente de Acreditación",
      "description": "Apoyo en procesos de acreditación",
      "status": "activo",
      "permissions": [...],
      "creadoEl": "2026-04-18 14:45:12",
      "actualizadoEl": "2026-04-18 14:45:12"
    }
  ]
}
```

---

## ✅ Activar Rol

### **3. PATCH /api/roles/{id}/activate**

**Activar el rol "Asistente de Acreditación" (ID: 4)**

```http
PATCH http://localhost:8000/api/roles/4/activate
Authorization: Bearer {{token}}
```

**Respuesta exitosa (200 OK):**
```json
{
  "message": "Rol activado"
}
```

**Respuesta si ya estaba activo (409 Conflict):**
```json
{
  "message": "El rol ya estaba activo",
  "role_id": 4
}
```

**Respuesta si el rol no existe (404 Not Found):**
```json
{
  "error": "Not Found",
  "message": "Rol no encontrado"
}
```

**Respuesta si no tienes permisos (403 Forbidden):**
```json
{
  "message": "This action is unauthorized."
}
```

---

## ❌ Desactivar Rol

### **4. PATCH /api/roles/{id}/deactivate**

**Desactivar el rol "Asistente de Acreditación" (ID: 4)**

```http
PATCH http://localhost:8000/api/roles/4/deactivate
Authorization: Bearer {{token}}
```

**Respuesta exitosa (200 OK):**
```json
{
  "message": "Rol desactivado"
}
```

**Respuesta si ya estaba inactivo (409 Conflict):**
```json
{
  "message": "El rol ya estaba inactivo",
  "role_id": 4
}
```

**Respuesta si es un rol protegido (403 Forbidden):**
```json
{
  "error": "Forbidden",
  "message": "Los roles del sistema no pueden ser desactivados"
}
```

**Ejemplo: Intentar desactivar "Superusuario" (ID: 1) — BLOQUEADO**
```http
PATCH http://localhost:8000/api/roles/1/deactivate
Authorization: Bearer {{token}}
```

```json
{
  "error": "Forbidden",
  "message": "Los roles del sistema no pueden ser desactivados"
}
```

---

## 🛡️ Roles Protegidos (NO se pueden desactivar)

Los siguientes roles están **protegidos** y **NO pueden ser desactivados**:

| ID | Nombre | Razón |
|----|--------|-------|
| 1  | Superusuario | Control total del sistema |
| 2  | Administrador | Gestión de usuarios y configuración |
| 3  | Encargado de Acreditación | Coordinación de procesos de acreditación |
| 5  | Profesor | Rol básico de docente |

Si intentas desactivar cualquiera de estos roles, obtendrás **403 Forbidden**.

---

## 📊 Verificar Estado Después de Activar/Desactivar

### **5. GET /api/roles/{id}**

**Ver estado del rol "Asistente de Acreditación" (ID: 4)**

```http
GET http://localhost:8000/api/roles/4
Authorization: Bearer {{token}}
```

**Respuesta:**
```json
{
  "data": {
    "id": 4,
    "name": "Asistente de Acreditación",
    "description": "Apoyo en procesos de acreditación",
    "status": "inactivo",  ← Estado actualizado
    "permissions": [...],
    "creadoEl": "2026-04-18 14:45:12",
    "actualizadoEl": "2026-04-18 15:30:45"  ← Fecha de actualización
  }
}
```

---

## 🔄 Flujo Completo en Postman

### **Escenario: Desactivar temporalmente un rol**

1. **Login** → Obtener token
   ```http
   POST /api/auth/login
   ```

2. **Listar roles** → Verificar estado actual
   ```http
   GET /api/roles
   ```

3. **Desactivar rol** → Desactivar "Asistente de Acreditación"
   ```http
   PATCH /api/roles/4/deactivate
   ```

4. **Verificar desactivación** → Ver estado
   ```http
   GET /api/roles/4
   ```
   ✅ `"status": "inactivo"`

5. **Activar nuevamente** → Reactivar rol
   ```http
   PATCH /api/roles/4/activate
   ```

6. **Verificar activación** → Ver estado
   ```http
   GET /api/roles/4
   ```
   ✅ `"status": "activo"`

---

## 🧪 Casos de Prueba

### ✅ **Caso 1: Activar rol inactivo**
```http
PATCH /api/roles/4/deactivate  # Desactivar primero
PATCH /api/roles/4/activate    # Activar → 200 OK
```

### ⚠️ **Caso 2: Activar rol ya activo**
```http
PATCH /api/roles/4/activate  # Primera vez → 200 OK
PATCH /api/roles/4/activate  # Segunda vez → 409 Conflict
```

### ❌ **Caso 3: Desactivar rol protegido**
```http
PATCH /api/roles/1/deactivate  # Superusuario → 403 Forbidden
```

### 🔍 **Caso 4: Activar rol inexistente**
```http
PATCH /api/roles/999/activate  # → 404 Not Found
```

---

## 📝 Registro en Bitácora

Cada acción se registra automáticamente en `BITACORA`:

**Tabla: `BITACORA`**

| bitacora_id | accion | descripcion | modulo | usuario_id | created_at |
|-------------|--------|-------------|--------|------------|------------|
| 1 | activar | Rol activado: Asistente de Acreditación (ID: 4) | Roles | 4 | 2026-04-18 15:30:45 |
| 2 | desactivar | Rol desactivado: Asistente de Acreditación (ID: 4) | Roles | 4 | 2026-04-18 15:32:10 |

---

## 🎯 IDs de Roles en el Sistema

| ID | Nombre | Protegido | Puede desactivarse |
|----|--------|-----------|-------------------|
| 1  | Superusuario | ✅ | ❌ |
| 2  | Administrador | ✅ | ❌ |
| 3  | Encargado de Acreditación | ✅ | ❌ |
| 4  | Asistente de Acreditación | ❌ | ✅ |
| 5  | Profesor | ✅ | ❌ |

---

## 🚀 Colección Postman

Crea una nueva colección con estas variables:

**Variables de entorno:**
```
base_url: http://localhost:8000/api
token: (se llena después del login)
```

**Requests:**
1. **Auth → Login**
   - POST `{{base_url}}/auth/login`
   - Guarda el token en variable de entorno

2. **Roles → Listar**
   - GET `{{base_url}}/roles`

3. **Roles → Activar**
   - PATCH `{{base_url}}/roles/4/activate`

4. **Roles → Desactivar**
   - PATCH `{{base_url}}/roles/4/deactivate`

5. **Roles → Ver detalle**
   - GET `{{base_url}}/roles/4`

---

## ✅ Checklist de Pruebas

- [ ] Login exitoso y token obtenido
- [ ] Listar roles muestra campo `status`
- [ ] Activar rol inactivo → 200 OK
- [ ] Activar rol activo → 409 Conflict
- [ ] Desactivar rol activo → 200 OK
- [ ] Desactivar rol inactivo → 409 Conflict
- [ ] Desactivar rol protegido → 403 Forbidden
- [ ] Activar rol inexistente → 404 Not Found
- [ ] Estado actualizado visible en GET /roles/{id}
- [ ] Registro en bitácora creado

---

## 🎉 ¡Listo para Usar!

Todos los endpoints están **funcionando correctamente** y **probados**.

**Comandos útiles:**
```bash
# Ver estado actual de roles en BD
php artisan tinker
>>> App\Models\Role::all(['id', 'name', 'status']);

# Ejecutar tests
php test_role_activate.php
```
