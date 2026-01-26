# Guía Completa Postman - RF-10 con Autenticación

## ⚠️ IMPORTANTE - Login con CÉDULA

El login se hace con **CÉDULA** (no email), como está configurado en LDAP.

---

## 📋 Paso a Paso en Postman

### 1️⃣ LOGIN (Obtener Token)

**Método:** `POST`  
**URL:** `http://localhost:8000/api/auth/login`  
**Body (JSON):**
```json
{
    "cedula": "203849675",
    "password": "password123"
}
```

**Respuesta esperada:**
```json
{
    "user": {
        "usuario_id": 1,
        "cedula": "203849675",
        "nombre": "Pablo Castillo Quesada",
        "email": "pablo.castillo.quesada@una.cr"
    },
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ..."
}
```

**👉 COPIA EL TOKEN** - Lo necesitarás para los siguientes pasos.

---

### 2️⃣ Configurar Authorization en Postman

Para **TODOS** los siguientes endpoints:

1. Ve a la pestaña **Authorization**
2. Type: **Bearer Token**
3. Token: [pega el token que copiaste]

---

### 3️⃣ Listar todas las aprobaciones

**Método:** `GET`  
**URL:** `http://localhost:8000/api/aprobaciones-criterios`  
**Authorization:** Bearer Token (el que copiaste)  
**Body:** Ninguno

**Respuesta esperada:**
```json
{
    "success": true,
    "data": [
        {
            "aprobacion_criterio_id": 1,
            "criterio_id": 1,
            "estado": "aprobado",
            "comentario": "...",
            "criterion": {
                "nomenclatura": "1.1.1"
            },
            "user": {
                "nombre": "Pablo Castillo Quesada"
            }
        }
    ]
}
```

---

### 4️⃣ Ver detalle de una aprobación

**Método:** `GET`  
**URL:** `http://localhost:8000/api/aprobaciones-criterios/1`  
**Authorization:** Bearer Token  
**Body:** Ninguno

---

### 5️⃣ Aprobar un criterio

**Método:** `POST`  
**URL:** `http://localhost:8000/api/criterios/5/aprobar`  
**Authorization:** Bearer Token  
**Body (JSON):**
```json
{
    "proceso_id": 1,
    "comentario": "Criterio aprobado - Todas las evidencias completas"
}
```

**✅ Qué hace:**
- Crea una aprobación con estado "aprobado"
- Registra en bitácora la acción
- Usa tu usuario_id automáticamente (del token)

---

### 6️⃣ Rechazar un criterio

**Método:** `POST`  
**URL:** `http://localhost:8000/api/criterios/6/rechazar`  
**Authorization:** Bearer Token  
**Body (JSON):**
```json
{
    "proceso_id": 1,
    "comentario": "Criterio requiere mejoras en documentación"
}
```

---

### 7️⃣ Verificar bitácora (opcional)

**Método:** `GET`  
**URL:** `http://localhost:8000/api/dev/bitacora`  
**Authorization:** No requiere  
**Body:** Ninguno

**Muestra:** Las últimas acciones registradas (aprobar, rechazar, etc.)

---

## 👥 Usuarios de prueba LDAP

Puedes hacer login con cualquiera de estos:

### Profesores:
- **Cédula:** `203849675` | **Password:** `password123` | Pablo Castillo
- **Cédula:** `203948609` | **Password:** `password123` | Cristopher Montero

### Estudiantes:
- **Cédula:** `208330811` | **Password:** `password123` | Jose Jara
- **Cédula:** `801490957` | **Password:** `password123` | Naydelin Jiron

---

## 🔐 Permisos por Rol

| Rol | Puede listar | Puede ver detalle | Puede aprobar | Puede rechazar |
|-----|--------------|-------------------|---------------|----------------|
| Superusuario | ✅ | ✅ | ✅ | ✅ |
| Encargado de Acreditación | ✅ | ✅ | ✅ | ✅ |
| Administrador | ✅ | ✅ | ❌ | ❌ |
| Profesor | ✅ | ✅ | ❌ | ❌ |

---

## ⚠️ Errores comunes

### Error 401 - No autorizado
**Causa:** No enviaste el token o expiró  
**Solución:** Haz login de nuevo y actualiza el token

### Error 403 - Forbidden
**Causa:** Tu rol no tiene permiso para esa acción  
**Solución:** Solo Encargado y Superusuario pueden aprobar/rechazar

### Error 400 - Criterio ya tiene aprobación
**Causa:** Ese criterio ya fue aprobado/rechazado antes  
**Solución:** Usa otro número de criterio (7, 8, 9, etc.)

---

## 🎯 Flujo completo de prueba

1. **POST** `/auth/login` → Copiar token
2. Configurar Authorization con el token en todos los requests
3. **GET** `/aprobaciones-criterios` → Ver las existentes
4. **POST** `/criterios/5/aprobar` → Aprobar uno nuevo
5. **POST** `/criterios/6/rechazar` → Rechazar otro
6. **GET** `/aprobaciones-criterios` → Verificar que se crearon
7. **GET** `/dev/bitacora` → Ver que se registró en bitácora

---

## ✅ Implementación completada

- ✅ Autenticación con LDAP (cédula + password)
- ✅ Autorización con Policies por rol
- ✅ Bitácora registra aprobar/rechazar
- ✅ Usuario_id automático desde Auth::id()
- ✅ Rate limiting (60 requests/min, 10 para crear)
