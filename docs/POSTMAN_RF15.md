# 📬 GUÍA POSTMAN - RF-15 Solicitudes de Ampliación

## 🎯 Configuración Inicial

### 1. Variables de Entorno

Crea un **Environment** en Postman llamado `SAAC-Local` con estas variables:

| Variable | Valor Inicial | Valor Actual |
|----------|---------------|--------------|
| `base_url` | `http://localhost:8000/api` | |
| `token` | | (se llenará automáticamente) |
| `token_jose` | | (opcional) |
| `token_pablo` | | (opcional) |
| `token_admin` | | (opcional) |

### 2. Crear Colección

Crea una colección llamada **"RF-15 Solicitudes Ampliación"** con las siguientes carpetas:
- 📁 Auth
- 📁 Solicitudes - CRUD
- 📁 Evidencias
- 📁 Pruebas de Seguridad

---

## 🔐 CARPETA: Auth

### 🟢 POST - Login Pablo (SuperUsuario)

**URL:** `{{base_url}}/auth/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
    "cedula": "203849675",
    "password": "password123"
}
```

**Tests (agregar en pestaña Tests):**
```javascript
// Guardar token automáticamente
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.token);
    pm.environment.set("token_pablo", jsonData.token);
    pm.environment.set("user_id", jsonData.user.id);
    
    console.log("✅ Login exitoso - SuperUsuario");
    console.log("Token guardado:", jsonData.token.substring(0, 30) + "...");
    console.log("User ID:", jsonData.user.id);
    console.log("Rol:", jsonData.user.roles[0].name);
}
```

**Resultado esperado:**
```json
{
    "user": {
        "id": 2,
        "name": "Pablo Castillo Quesada",
        "email": "pablo.castillo.quesada@est.una.ac.cr",
        "cedula": "203849675",
        "roles": [
            {
                "id": 1,
                "name": "SuperUsuario"
            }
        ]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxx"
}
```

---

### 🟢 POST - Login José (Profesor)

**URL:** `{{base_url}}/auth/login`

**Body:**
```json
log("✅ Login José (Profesor) exitoso");
}
```

---

### 🟢 POST - Login Admin

**URL:** `{{base_url}}/auth/login`

**Body:**
```json
{
    "cedula": "203948609",
    "password": "password123"
}
```

**Tests:**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.token);
    pm.environment.set("token_admin", jsonData.token);
    console.log("✅ Login Admin exitoso");
}
```

---

## 📋 CARPETA: Solicitudes - CRUD

### 🟢 GET - Listar Solicitudes

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo`

**Headers:**
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Query Params (opcionales):**
```
per_page: 15
estado: Pendiente
fecha_desde: 2026-01-01
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has data array", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.be.an('array');
});

// Guardar primer ID para pruebas
var jsonData = pm.response.json();
if (jsonData.data.length > 0) {
    pm.environment.set("solicitud_id", jsonData.data[0].solicitud_ampliacion_id);
    console.log("Total solicitudes:", jsonData.meta.total);
}
```

**Resultado esperado (Pablo ve TODAS):**
```json
{
    "data": [
        {
            "solicitud_ampliacion_id": 10,
            "usuario_id": 4,
            "evidencia_asignacion_id": 6,
            "estado": "Pendiente",
            "motivo": "Solicito ampliación...",
            "fecha_solicitud": "2026-01-16 18:59:56",
            "fecha_sugerida": "2026-05-15",
            "fecha_resolucion": null,
            "justificacion": null
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 5,
        "per_page": 15
    }
}
```

---

### 🟢 GET - Ver Solicitud Específica

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo/{{solicitud_id}}`

**Headers:**
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has solicitud data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property('solicitud_ampliacion_id');
    pm.expect(jsonData.data).to.have.property('estado');
});
```

---

### 🟢 POST - Crear Solicitud

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo`

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
    "evidencia_asignacion_id": 1,
    "motivo": "Solicito ampliación de tiempo para completar la evidencia debido a responsabilidades académicas importantes que requieren mi atención inmediata.",
    "fecha_sugerida": "2026-06-30"
}
```

**Tests:**
```javascript
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("nueva_solicitud_id", jsonData.data.solicitud_ampliacion_id);
    console.log("✅ Solicitud creada ID:", jsonData.data.solicitud_ampliacion_id);
}

pm.test("Status code is 201", function () {
    pm.response.to.have.status(201);
});

pm.test("Response has created data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.estado).to.eql("Pendiente");
});
```

**Respuesta esperada (201):**
```json
{
    "message": "Solicitud de ampliación creada exitosamente.",
    "data": {
        "solicitud_ampliacion_id": 11,
        "usuario_id": 2,
        "evidencia_asignacion_id": 1,
        "estado": "Pendiente",
        "motivo": "Solicito ampliación...",
        "fecha_sugerida": "2026-06-30",
        "created_at": "2026-01-16 20:30:00"
    }
}
```

---

### 🟡 PUT - Actualizar Solicitud

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo/{{nueva_solicitud_id}}`

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
    "motivo": "Motivo actualizado: Requiero tiempo adicional debido a nueva carga académica asignada que requiere dedicación exclusiva durante las próximas semanas.",
    "fecha_sugerida": "2026-07-31"
}
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Solicitud fue actualizada", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include("actualizada");
});
```

**Respuesta esperada (200):**
```json
{
    "message": "Solicitud actualizada exitosamente.",
    "data": {
        "solicitud_ampliacion_id": 11,
        "motivo": "Motivo actualizado: Requiero tiempo adicional...",
        "fecha_sugerida": "2026-07-31",
        "updated_at": "2026-01-16 20:35:00"
    }
}
```

---

### 🔴 DELETE - Eliminar Solicitud

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo/{{nueva_solicitud_id}}`

**Headers:**
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Solicitud fue eliminada", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include("eliminada");
});
```

**Respuesta esperada (200):**
```json
{
    "message": "Solicitud eliminada exitosamente."
}
```

---

## 📊 CARPETA: Evidencias

### 🟢 GET - Evidencias Próximas a Vencer

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer`

**Headers:**
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has total field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('total');
    console.log("Evidencias próximas:", jsonData.total);
});
```

**Respuesta esperada:**
```json
{
    "data": [],
    "total": 0,
    "message": "No tiene evidencias próximas a vencer en los próximos 7 días."
}
```

---

## 🔒 CARPETA: Pruebas de Seguridad

### ❌ POST - Intentar Crear Duplicado (Debe fallar 422)

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo`

**Headers:**
```
Authorization: Bearer {{token_jose}}
Content-Type: application/json
```

**Body:**
```json
{
    "evidencia_asignacion_id": 6,
    "motivo": "Intento crear duplicado para evidencia que ya tiene solicitud pendiente",
    "fecha_sugerida": "2026-07-15"
}
```

**Tests:**
```javascript
pm.test("Status code is 422 (validación rechaza duplicado)", function () {
    pm.response.to.have.status(422);
});

pm.test("Error message menciona duplicado", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.errors.evidencia_asignacion_id[0]).to.include("PENDIENTE");
});
```

**Respuesta esperada (422):**
```json
{
    "message": "Los datos proporcionados no son válidos.",
    "errors": {
        "evidencia_asignacion_id": [
            "Ya tiene una solicitud de ampliación PENDIENTE para esta evidencia. Debe esperar su resolución antes de crear otra."
        ]
    }
}
```

---

### ❌ PUT - Intentar Actualizar Solicitud Aprobada (Debe fallar 422)

**URL:** `{{base_url}}/solicitudes-ampliacion-tiempo/1`

**Headers:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
```

**Body:**
```json
{
    "motivo": "Intento actualizar solicitud no pendiente",
    "fecha_sugerida": "2026-08-01"
}
```

**Tests:**
```javascript
pm.test("Status code is 422", function () {
    pm.response.to.have.status(422);
});
```

**Respuesta esperada (422):**
```json
{
    "message": "Solo se pueden actualizar solicitudes pendientes"
}
```

---

## 🎭 Probar Filtro por Rol

### Test 1: Pablo (SuperUsuario) ve TODAS

1. Login con Pablo: `POST /auth/login` (cedula: 118240882)
2. Listar: `GET /solicitudes-ampliacion-tiempo`
3. **Esperado:** Ve TODAS las solicitudes (total >= 5)

### Test 2: José (Profesor) ve SOLO las suyas

1. Login con José: `POST /auth/login` (cedula: 208330811)
2. Listar: `GET /solicitudes-ampliacion-tiempo`
3. **Esperado:** Ve solo 1 solicitud (usuario_id = 4)

### Test 3: Admin ve TODAS

1. Login con Admin: `POST /auth/login` (cedula: 203948609)
2. Listar: `GET /solicitudes-ampliacion-tiempo`
3. **Esperado:** Ve TODAS las solicitudes

---

## 🔧 Pre-request Scripts Útiles

### Auto-Login (agregar en Collection Pre-request Scripts)

```javascript
// Si no hay token, hacer login automáticamente
if (!pm.environment.get("token")) {
    pm.sendRequest({
        url: pm.environment.get("base_url") + "/auth/login",
        method: 'POST',
        header: {
            'Content-Type': 'application/json'
        },
        body: {
            mode: 'raw',
            raw: JSON.stringify({
                cedula: "203849675",
                password: "password123"
            })
        }
    }, function (err, res) {
        if (!err) {
            var jsonData = res.json();
            pm.environment.set("token", jsonData.token);
            console.log("🔐 Auto-login exitoso");
        }
    });
}
```

---

## 👥 Tabla de Usuarios para Pruebas

| Nombre | Cédula | Password | Rol | User ID |
|--------|--------|----------|-----|---------|
| Pablo Castillo | 203849675 | password123 | **SuperUsuario** | 2 |
| José Jara | 208330811 | password123 | Profesor | 4 |
| Admin | 203948609 | password123 | Administrador | 1 |
| Encargado | 117540925 | password123 | Encargado | 3 |

---

## 📦 Importar Colección (JSON)

Copia este JSON en un archivo `RF15-Postman-Collection.json` y luego **Import** en Postman:

```json
{
    "info": {
        "name": "RF-15 Solicitudes Ampliación",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000/api"
        }
    ],
    "item": [
        {
            "name": "Auth",
            "item": [
                {
                    "name": "Login Pablo (SuperUsuario)",
                    "request": {
                        "method": "POST",
                        "header": [
                            {"key": "Content-Type", "value": "application/json"},
                            {"key": "Accept", "value": "application/json"}
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"cedula\": \"118240882\",\n    \"password\": \"password123\"\n}"
                        },
                        "url": "{{base_url}}/auth/login"
                    }
                }
            ]
        }
    ]
}
```

---

## ✅ Checklist de Pruebas

- [ ] Login Pablo (SuperUsuario) - 200
- [ ] Login José (Profesor) - 200
- [ ] Login Admin - 200
- [ ] Pablo lista TODAS las solicitudes
- [ ] José lista SOLO sus solicitudes
- [ ] Crear solicitud válida - 201
- [ ] Intentar crear duplicado - 422
- [ ] Ver detalle solicitud - 200
- [ ] Actualizar solicitud PENDIENTE - 200
- [ ] Intentar actualizar NO pendiente - 422
- [ ] Eliminar solicitud PENDIENTE - 200
- [ ] Evidencias próximas a vencer - 200

---

## 🐛 Troubleshooting

**Error 401 "Unauthenticated":**
- Token expirado (24h) → Hacer login de nuevo
- Verificar header: `Authorization: Bearer TOKEN_AQUI`

**Error 422 "Validación":**
- `evidencia_asignacion_id` no existe o ya tiene solicitud PENDIENTE
- Fecha sugerida debe ser FUTURA (after:today)
- Motivo debe tener mínimo 10 caracteres

**Error 500:**
- Revisar logs Laravel: `storage/logs/laravel.log`
- Limpiar caché: `php artisan cache:clear`
