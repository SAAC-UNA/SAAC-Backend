# 🔧 Solución de Problemas - Testing en Postman

## ❌ Problemas encontrados:

### 1. Error 500 en POST `/estructura/procesos`
**Causa:** Faltan datos necesarios o error de validación

### 2. Error 404 en POST `/estructura/jerarquia`  
**Causa:** Caché de rutas o permisos

---

## ✅ SOLUCIÓN PASO A PASO

### Paso 1: Limpiar cachés de Laravel
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Paso 2: Verificar datos necesarios
```bash
php artisan db:seed --class=TestDataSeeder
```

Esto te mostrará los IDs correctos para usar en Postman.

---

## 📝 Datos correctos para Postman

### ✅ 1. CREAR PROCESO

**URL:**
```
POST http://localhost:8000/api/estructura/procesos
```

**Headers:**
```
Authorization: Bearer {tu_token}
Content-Type: application/json
Accept: application/json
```

**Body (usar estos IDs exactos):**
```json
{
  "ciclo_acreditacion_id": 1,
  "tipo_proceso": "autoevaluacion",
  "modelo_estructura_id": 1,
  "activo": true
}
```

---

### ✅ 2. CREAR JERARQUÍA

**URL:**
```
POST http://localhost:8000/api/estructura/jerarquia
```

**Headers:**
```
Authorization: Bearer {tu_token}
Content-Type: application/json
Accept: application/json
```

**Body (elemento raíz):**
```json
{
  "modelo_estructura_id": 2,
  "parent_id": null,
  "nombre": "Gestión Académica",
  "tipo": "dimension",
  "categoria": "A",
  "nomenclatura": "D1",
  "descripcion": "Dimensión de gestión académica",
  "orden": 1,
  "activo": true
}
```

---

## 🔍 Verificar Token y Permisos

### 1. Obtener token válido:
```
POST http://localhost:8000/api/auth/login
```

**Body:**
```json
{
  "email": "tu_email@example.com",
  "password": "tu_password"
}
```

**Copiar el `token` de la respuesta**

---

### 2. Verificar permisos del usuario:
```
GET http://localhost:8000/api/auth/permissions
```

**Headers:**
```
Authorization: Bearer {tu_token}
```

**Debe incluir:**
- `jerarquia.create`
- `jerarquia.edit`
- `jerarquia.view`
- `ciclos.create`
- `ciclos.edit`
- `ciclos.view`

---

## 🐛 Si aún hay errores

### Error 401 (Unauthorized)
- **Causa:** Token inválido o expirado
- **Solución:** Hacer login nuevamente y obtener nuevo token

### Error 403 (Forbidden)
- **Causa:** Usuario no tiene los permisos necesarios
- **Solución:** Asignar permisos al usuario o usar usuario Superusuario

### Error 404 (Not Found)
- **Causa:** Ruta no existe o caché desactualizado
- **Solución:** 
  ```bash
  php artisan route:clear
  php artisan config:clear
  ```

### Error 422 (Validation Error)
- **Causa:** Datos inválidos en el body
- **Solución:** Verificar que todos los campos obligatorios estén presentes
- Revisar que los IDs existan en la base de datos

### Error 500 (Internal Server Error)
- **Causa:** Error en el servidor (FK inexistente, error de código)
- **Solución:** 
  1. Verificar logs: `storage/logs/laravel.log`
  2. Ejecutar `TestDataSeeder` para crear datos necesarios
  3. Verificar que existan:
     - CICLO_ACREDITACION con ID especificado
     - MODELO_ESTRUCTURA con ID especificado

---

## 📊 Verificar datos en BD

### Opción 1: Via Tinker
```bash
php artisan tinker
```
```php
// Ver ciclos disponibles
DB::table('CICLO_ACREDITACION')->get(['ciclo_acreditacion_id', 'tipo_proceso', 'activo']);

// Ver modelos disponibles  
DB::table('MODELO_ESTRUCTURA')->get(['modelo_estructura_id', 'nombre', 'tipo']);
```

### Opción 2: Via Seeder
```bash
php artisan db:seed --class=TestDataSeeder
```

---

## 🧪 Orden de testing recomendado

1. ✅ **Limpiar cachés**
   ```bash
   php artisan cache:clear && php artisan route:clear && php artisan config:clear
   ```

2. ✅ **Verificar datos**
   ```bash
   php artisan db:seed --class=TestDataSeeder
   ```

3. ✅ **Hacer login**
   ```
   POST /api/auth/login
   ```

4. ✅ **Copiar token a variable de Postman**
   - Token → `{{auth_token}}`

5. ✅ **Listar modelos**
   ```
   GET /api/estructura/modelos/activos
   ```

6. ✅ **Listar ciclos**
   ```
   GET /api/estructura/ciclos-acreditacion
   ```

7. ✅ **Crear proceso**
   ```
   POST /api/estructura/procesos
   Body: { "ciclo_acreditacion_id": 1, "tipo_proceso": "autoevaluacion", "modelo_estructura_id": 1 }
   ```

8. ✅ **Crear jerarquía**
   ```
   POST /api/estructura/jerarquia
   Body: { "modelo_estructura_id": 2, "parent_id": null, "nombre": "Gestión Académica", "tipo": "dimension", "nomenclatura": "D1", "orden": 1 }
   ```

---

## 📞 Checklist de debugging

- [ ] Cachés limpiados (route, config, cache)
- [ ] Token de autenticación válido
- [ ] Usuario tiene permisos necesarios
- [ ] Headers correctos (Authorization, Content-Type, Accept)
- [ ] IDs de FK existen en BD (ciclo_acreditacion_id, modelo_estructura_id)
- [ ] Body en formato JSON válido
- [ ] Endpoint correcto (http://localhost:8000/api/...)
- [ ] Método HTTP correcto (POST)

---

## 🎯 Respuestas esperadas

### ✅ Proceso creado:
```json
{
  "message": "Proceso creado exitosamente.",
  "data": {
    "proceso_id": 2,
    "ciclo_acreditacion_id": 1,
    "tipo_proceso": "autoevaluacion",
    "modelo_estructura_id": 1,
    "activo": true,
    "created_at": "2026-03-15T...",
    "updated_at": "2026-03-15T...",
    "modeloEstructura": {
      "modelo_estructura_id": 1,
      "nombre": "SINAES 2018",
      "tipo": "tradicional"
    }
  }
}
```

### ✅ Jerarquía creada:
```json
{
  "message": "Elemento de jerarquía creado exitosamente.",
  "data": {
    "jerarquia_id": 1,
    "modelo_estructura_id": 2,
    "parent_id": null,
    "nombre": "Gestión Académica",
    "tipo": "dimension",
    "categoria": "A",
    "nomenclatura": "D1",
    "orden": 1,
    "activo": true,
    "created_at": "2026-03-15T...",
    "updated_at": "2026-03-15T..."
  }
}
```
