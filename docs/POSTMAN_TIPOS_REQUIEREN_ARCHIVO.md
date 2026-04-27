# 🚀 GUÍA POSTMAN: tipos_requieren_archivo

## 📋 **TABLA: MODELO_ESTRUCTURA**

La columna `tipos_requieren_archivo` se agregó en la tabla **MODELO_ESTRUCTURA**:

```
Columna: tipos_requieren_archivo
Tipo: JSON
Permite NULL: Sí
```

### **Datos actuales:**
- **Modelo 1** (SINAES 2018): `["Pauta","Indicador"]`
- **Modelo 2** (SINAES 2026): `null`
- **Modelo 3** (Modelo test): `null`

---

## 🔐 **PASO 1: AUTENTICACIÓN**

### **Login**
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "tu_email@example.com",
  "password": "tu_password"
}
```

**Respuesta:**
```json
{
  "token": "1|abcdef123456...",
  "user": { ... }
}
```

**⚠️ IMPORTANTE:** Copia el token para usarlo en todas las siguientes peticiones.

---

## 📖 **PASO 2: VER MODELOS EXISTENTES**

### **Listar todos los modelos**
```
GET http://localhost:8000/api/estructura/modelos
Authorization: Bearer {TU_TOKEN_AQUI}
```

**Respuesta esperada:**
```json
[
  {
    "modelo_estructura_id": 1,
    "nombre": "SINAES 2018 - Estructura Tradicional",
    "tipo": "tradicional",
    "activo": true,
    "tipos_asignables": ["Pauta", "Fuente"],
    "tipos_jerarquia": null,
    "tipos_requieren_archivo": ["Pauta", "Indicador"]  // 👈 NUEVA COLUMNA
  },
  {
    "modelo_estructura_id": 2,
    "nombre": "SINAES 2026 - Estructura Flexible",
    "tipo": "elemento_flexible",
    "activo": true,
    "tipos_requieren_archivo": null
  }
]
```

---

## ✏️ **PASO 3: CONFIGURAR tipos_requieren_archivo**

### **Actualizar modelo existente**
```
PATCH http://localhost:8000/api/estructura/modelos/1
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: application/json

{
  "tipos_requieren_archivo": ["Pauta", "Indicador", "Fuente"]
}
```

**Respuesta esperada:**
```json
{
  "message": "Modelo actualizado exitosamente",
  "modelo": {
    "modelo_estructura_id": 1,
    "nombre": "SINAES 2018 - Estructura Tradicional",
    "tipos_requieren_archivo": ["Pauta", "Indicador", "Fuente"]
  }
}
```

---

## 🧪 **PASO 4: PROBAR VALIDACIÓN DE ARCHIVOS**

### **4A. Subir archivo a un tipo PERMITIDO (debe funcionar)**

Primero necesitas un elemento del tipo "Pauta" o "Indicador":

```
GET http://localhost:8000/api/estructura/elementos?tipo=Pauta
Authorization: Bearer {TU_TOKEN_AQUI}
```

Luego sube un archivo:

```
POST http://localhost:8000/api/elementos-archivos
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: multipart/form-data

Form Data:
- elemento_id: 1 (o el ID del elemento tipo "Pauta")
- archivo: [Seleccionar archivo PDF/imagen]
- nombre: "Evidencia de Pauta"
- descripcion: "Prueba de archivo permitido"
```

**✅ Resultado esperado:** Archivo subido exitosamente

---

### **4B. Subir archivo a un tipo NO PERMITIDO (debe bloquearse)**

Busca un elemento tipo "Dimensión":

```
GET http://localhost:8000/api/estructura/elementos?tipo=Dimensión
Authorization: Bearer {TU_TOKEN_AQUI}
```

Intenta subir un archivo:

```
POST http://localhost:8000/api/elementos-archivos
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: multipart/form-data

Form Data:
- elemento_id: X (ID del elemento tipo "Dimensión")
- archivo: [Seleccionar archivo]
- nombre: "Test bloqueado"
```

**❌ Resultado esperado:**
```json
{
  "error": "El tipo 'Dimensión' no está permitido para subir archivos en este modelo. Tipos permitidos: Pauta, Indicador, Fuente"
}
```

---

## 🔍 **PASO 5: FILTRAR ELEMENTOS QUE REQUIEREN ARCHIVOS**

### **Filtrar elementos que PUEDEN tener archivos**
```
GET http://localhost:8000/api/estructura/elementos/filter?requiere_archivo=true
Authorization: Bearer {TU_TOKEN_AQUI}
```

**Respuesta:** Solo elementos de tipo "Pauta", "Indicador", "Fuente"

---

### **Filtrar elementos que FALTAN archivos**
```
GET http://localhost:8000/api/estructura/elementos/filter?falta_archivo=true
Authorization: Bearer {TU_TOKEN_AQUI}
```

**Respuesta:** Elementos que pueden tener archivos pero aún no tienen ninguno subido

---

## 📝 **PASO 6: CREAR NUEVO MODELO CON CONFIGURACIÓN**

```
POST http://localhost:8000/api/estructura/modelos
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: application/json

{
  "nombre": "Modelo Acreditación 2027",
  "descripcion": "Modelo con validación de archivos",
  "tipo": "elemento_flexible",
  "tipos_requieren_archivo": ["Indicador", "Evidencia"],
  "tipos_asignables": ["Indicador"],
  "activo": true
}
```

**Respuesta esperada:**
```json
{
  "message": "Modelo creado exitosamente",
  "modelo": {
    "modelo_estructura_id": 4,
    "nombre": "Modelo Acreditación 2027",
    "tipos_requieren_archivo": ["Indicador", "Evidencia"],
    "tipos_asignables": ["Indicador"]
  }
}
```

---

## 🎯 **RESUMEN DE ENDPOINTS**

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/estructura/modelos` | Ver todos los modelos |
| GET | `/api/estructura/modelos/{id}` | Ver un modelo específico |
| POST | `/api/estructura/modelos` | Crear modelo con tipos_requieren_archivo |
| PATCH | `/api/estructura/modelos/{id}` | Actualizar tipos_requieren_archivo |
| POST | `/api/elementos-archivos` | Subir archivo (valida tipos_requieren_archivo) |
| GET | `/api/estructura/elementos/filter?requiere_archivo=true` | Filtrar elementos que pueden tener archivos |
| GET | `/api/estructura/elementos/filter?falta_archivo=true` | Filtrar elementos sin archivos |

---

## ✅ **VALIDACIONES AUTOMÁTICAS**

La validación en `FlexibleFileService::validarTipoAsignable()` verifica:

1. ✅ Si `tipos_requieren_archivo` es `null` o `[]` → **Permite todos los tipos**
2. ✅ Si el tipo del elemento está en el array → **Permite subir archivo**
3. ❌ Si el tipo NO está en el array → **Bloquea con error 403**

---

## 🔧 **VARIABLES DE ENTORNO POSTMAN**

Crea estas variables en Postman:

```
BASE_URL = http://localhost:8000/api
TOKEN = (se actualiza después del login)
MODELO_ID = 1
ELEMENTO_ID = (ID de un elemento de prueba)
```

---

## 📊 **CASOS DE PRUEBA COMPLETOS**

### **Test 1: Modelo sin restricciones**
```json
{
  "tipos_requieren_archivo": null
}
```
✅ Resultado: Todos los tipos pueden subir archivos

---

### **Test 2: Modelo con restricciones**
```json
{
  "tipos_requieren_archivo": ["Pauta", "Indicador"]
}
```
✅ "Pauta" puede subir archivos
✅ "Indicador" puede subir archivos  
❌ "Dimensión" bloqueado
❌ "Fuente" bloqueado

---

### **Test 3: Modelo flexible completo**
```json
{
  "tipos_asignables": ["Pauta", "Fuente"],
  "tipos_requieren_archivo": ["Pauta", "Indicador"]
}
```

| Tipo | ¿Asignable? | ¿Archivos? |
|------|-------------|------------|
| Pauta | ✅ Sí | ✅ Sí |
| Fuente | ✅ Sí | ❌ No |
| Indicador | ❌ No | ✅ Sí |
| Dimensión | ❌ No | ❌ No |

---

## 🚨 **ERRORES COMUNES**

### Error 401: Unauthorized
**Causa:** Token inválido o expirado  
**Solución:** Volver a hacer login

### Error 403: Forbidden
**Causa:** Sin permisos `modelos.view` o `modelos.edit`  
**Solución:** Asignar permisos al usuario

### Error 422: Validation Error
**Causa:** JSON inválido en `tipos_requieren_archivo`  
**Solución:** Debe ser array de strings válidos (max 30 caracteres cada uno)

### Error 404: Not Found
**Causa:** modelo_estructura_id no existe  
**Solución:** Verificar IDs disponibles con GET `/modelos`

---

## 🎉 **CONCLUSIÓN**

La columna `tipos_requieren_archivo` está **100% funcional** y se puede probar completamente desde Postman siguiendo estos pasos. 

**Sin necesidad del archivo de migración** - la columna está directo en la tabla MySQL.
