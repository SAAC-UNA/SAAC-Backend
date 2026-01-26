# 🧪 Pruebas de Autorización HU-002

**Servidor**: http://127.0.0.1:8000  
**Fecha**: 9 Enero 2026

---

## 📋 Pasos para Probar

### 1️⃣ **Iniciar servicios necesarios**

```bash
# Terminal 1: Iniciar Docker (LDAP + Redis)
cd SAAC-Backend
docker compose up -d

# Terminal 2: Iniciar Laravel
php artisan serve
# Servidor corriendo en: http://127.0.0.1:8000
```

---

### 2️⃣ **Probar SIN TOKEN (debe dar 401)**

**Usando PowerShell:**
```powershell
curl http://127.0.0.1:8000/api/admin/users
```

**Usando Postman/Thunder Client:**
```
GET http://127.0.0.1:8000/api/admin/users
Headers: (vacío, sin Authorization)
```

**Resultado esperado:**
```json
{
  "message": "Unauthenticated."
}
```
**Status Code**: `401 Unauthorized` ✅

---

### 3️⃣ **Obtener TOKEN (Login con LDAP)**

**Endpoint de login:**
```
POST http://127.0.0.1:8000/api/auth/login
```

**Body (JSON):**
```json
{
  "cedula": "203849675",
  "password": "password123"
}
```

**Usuarios de prueba disponibles:**

| Usuario | Cédula | Password | Rol | Tiene `usuarios.edit` |
|---------|--------|----------|-----|-----------------------|
| Admin | 203849675 | password123 | Superusuario | ✅ SÍ |
| Evaluador | 104560782 | password123 | Evaluador | ❌ NO |

**Respuesta esperada:**
```json
{
  "message": "Login exitoso",
  "user": {
    "usuario_id": 1,
    "nombre": "Carlos Rodríguez Mora",
    "email": "admin@saacuna.local",
    "cedula": "203849675",
    "roles": ["Superusuario"],
    "permissions": ["usuarios.view", "usuarios.create", "usuarios.edit", ...]
  },
  "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ123456789..."
}
```

**Copiar el `token` de la respuesta** 📋

---

### 4️⃣ **Probar CON TOKEN pero SIN PERMISO (debe dar 403)**

Login con **Evaluador** (que NO tiene `usuarios.edit`):

**1. Login:**
```json
POST http://127.0.0.1:8000/api/auth/login
{
  "cedula": "104560782",
  "password": "password123"
}
```

**2. Copiar token del Evaluador**

**3. Intentar listar usuarios:**
```
GET http://127.0.0.1:8000/api/admin/users
Headers:
  Authorization: Bearer TOKEN_DEL_EVALUADOR
  Accept: application/json
```

**Resultado esperado:**
```json
{
  "message": "User does not have the right permissions."
}
```
**Status Code**: `403 Forbidden` ✅

---

### 5️⃣ **Probar CON TOKEN Y CON PERMISO (debe funcionar)**

Login con **Admin/Superusuario** (que SÍ tiene `usuarios.edit`):

**1. Login:**
```json
POST http://127.0.0.1:8000/api/auth/login
{
  "cedula": "203849675",
  "password": "password123"
}
```

**2. Copiar token del Admin**

**3. Listar usuarios:**
```
GET http://127.0.0.1:8000/api/admin/users
Headers:
  Authorization: Bearer TOKEN_DEL_ADMIN
  Accept: application/json
```

**Resultado esperado:**
```json
{
  "data": [
    {
      "usuario_id": 1,
      "nombre": "Carlos Rodríguez Mora",
      "email": "admin@saacuna.local",
      "status": "active",
      "roles": ["Superusuario"],
      "permissions": [...]
    },
    {
      "usuario_id": 2,
      "nombre": "María González López",
      ...
    }
  ]
}
```
**Status Code**: `200 OK` ✅

---

### 6️⃣ **Probar ACTIVAR usuario (con permiso)**

```
PATCH http://127.0.0.1:8000/api/admin/users/2/activate
Headers:
  Authorization: Bearer TOKEN_DEL_ADMIN
  Accept: application/json
```

**Resultado esperado:**
```json
{
  "message": "Usuario activado"
}
```
**Status Code**: `200 OK` ✅

**Verificar en bitácora** (opcional):
```
GET http://127.0.0.1:8000/api/bitacora
```

---

### 7️⃣ **Probar ASIGNAR ROL (con permiso)**

```
PUT http://127.0.0.1:8000/api/admin/users/3/role
Headers:
  Authorization: Bearer TOKEN_DEL_ADMIN
  Content-Type: application/json
  Accept: application/json
Body:
{
  "role": "Evaluador"
}
```

**Resultado esperado:**
```json
{
  "message": "Rol asignado correctamente",
  "user_id": 3,
  "role": "Evaluador"
}
```
**Status Code**: `200 OK` ✅

---

## 📊 Resumen de Pruebas

| # | Prueba | Token | Permiso | Resultado Esperado |
|---|--------|-------|---------|-------------------|
| 1 | GET /api/admin/users | ❌ No | - | 401 Unauthorized |
| 2 | GET /api/admin/users | ✅ Evaluador | ❌ No | 403 Forbidden |
| 3 | GET /api/admin/users | ✅ Admin | ✅ Sí | 200 OK + Lista |
| 4 | PATCH .../activate | ✅ Admin | ✅ Sí | 200 OK |
| 5 | PUT .../role | ✅ Admin | ✅ Sí | 200 OK |
| 6 | PUT .../permissions | ✅ Admin | ✅ Sí | 200 OK |

---

## 🔧 Comandos PowerShell Completos

```powershell
# Prueba 1: Sin token (401)
curl http://127.0.0.1:8000/api/admin/users

# Prueba 2: Login Admin
$loginResponse = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/login" `
  -Method POST `
  -Body (@{cedula="203849675"; password="password123"} | ConvertTo-Json) `
  -ContentType "application/json"

# Guardar token
$token = $loginResponse.token

# Prueba 3: Listar usuarios con token
$headers = @{
  "Authorization" = "Bearer $token"
  "Accept" = "application/json"
}

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/users" `
  -Method GET `
  -Headers $headers

# Prueba 4: Activar usuario
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/users/2/activate" `
  -Method PATCH `
  -Headers $headers
```

---

## ✅ Checklist de Validación

- [ ] Servidor Laravel corriendo (`php artisan serve`)
- [ ] Docker corriendo (LDAP + Redis)
- [ ] Prueba 1: Sin token → 401 ✅
- [ ] Prueba 2: Con token de Evaluador → 403 ✅
- [ ] Prueba 3: Con token de Admin → 200 OK ✅
- [ ] Prueba 4: Activar usuario → 200 OK ✅
- [ ] Prueba 5: Asignar rol → 200 OK ✅
- [ ] Verificar registro en bitácora

---

**Creado**: 9 Enero 2026  
**Última actualización**: 9 Enero 2026
