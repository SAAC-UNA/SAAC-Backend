# ⚠️ VERIFICACIÓN DE CONFIGURACIÓN LDAP

## Estado Actual

La configuración de LDAP está presente en el archivo `config/ldap.php`, pero **no se pudo verificar** si está activa o desactivada porque el archivo `.env` no es accesible.

## ¿Cómo Desactivar LDAP?

### Opción 1: Comentar Variables en .env (Recomendado)

Abre el archivo `.env` en la raíz del proyecto y **comenta o elimina** todas las variables relacionadas con LDAP:

```env
# LDAP DESACTIVADO - Descomentar solo en producción
# LDAP_CONNECTION=default
# LDAP_HOST=ldap.example.com
# LDAP_PORT=389
# LDAP_USERNAME=cn=admin,dc=example,dc=com
# LDAP_PASSWORD=secret
# LDAP_BASE_DN=dc=example,dc=com
# LDAP_USE_SSL=false
# LDAP_USE_TLS=false
# LDAP_SSL_VERIFY=false
```

### Opción 2: Agregar Variable de Control

Agrega una variable de control en `.env`:

```env
LDAP_ENABLED=false
```

Y modifica el código de autenticación para verificar esta variable antes de intentar conectar a LDAP.

## ¿Cómo Verificar si LDAP Está Activo?

### 1. Revisar el archivo .env

```bash
# En terminal PowerShell
Get-Content .env | Select-String -Pattern "LDAP"
```

### 2. Revisar Logs de Laravel

```bash
# Ver últimas líneas del log
Get-Content storage\logs\laravel.log -Tail 50
```

Busca errores como:
- `LDAP connection failed`
- `Could not connect to LDAP`
- `LDAP authentication error`

### 3. Probar Autenticación

Intenta iniciar sesión en el sistema:
- Si falla con error de LDAP → LDAP está activo
- Si funciona con usuario local → LDAP está desactivado

## Configuración Recomendada para Desarrollo

```env
# =====================================================
# CONFIGURACIÓN DE AUTENTICACIÓN - DESARROLLO LOCAL
# =====================================================

# Desactivar LDAP en desarrollo
LDAP_ENABLED=false

# Usar autenticación local (base de datos)
AUTH_GUARD=api
AUTH_PROVIDER=users

# =====================================================
# LDAP (Solo para producción - Mantener comentado)
# =====================================================
# LDAP_CONNECTION=default
# LDAP_HOST=ldap.una.ac.cr
# LDAP_PORT=389
# LDAP_USERNAME=cn=admin,dc=una,dc=ac,dc=cr
# LDAP_PASSWORD=
# LDAP_BASE_DN=dc=una,dc=ac,dc=cr
# LDAP_USE_SSL=false
# LDAP_USE_TLS=false
# LDAP_SSL_VERIFY=false
```

## Archivos Relacionados con LDAP

1. **Configuración**:
   - `config/ldap.php` - Configuración de conexión LDAP
   - `config/auth.php` - Configuración de autenticación

2. **Documentación**:
   - `docs/AUTENTICACION_LDAP_REDIS.md` - Guía de autenticación LDAP
   - `docs/CONFIGURACION_LDAP.md` - Configuración detallada

3. **Código**:
   - `app/Models/User.php` - Modelo de usuario (menciona LDAP en comentarios)

## Próximos Pasos

1. ✅ **Revisar archivo .env** - Verificar estado de variables LDAP
2. ✅ **Comentar variables LDAP** - Si estás en desarrollo local
3. ✅ **Probar autenticación** - Iniciar sesión para confirmar
4. ✅ **Revisar logs** - Buscar errores relacionados con LDAP
5. ✅ **Documentar cambios** - Registrar qué se modificó

## Comando Rápido de Verificación

```powershell
# Verificar si LDAP está configurado en .env
if (Select-String -Path .env -Pattern "LDAP_HOST" -Quiet) {
    Write-Host "⚠️ LDAP está configurado en .env" -ForegroundColor Yellow
    Get-Content .env | Select-String -Pattern "LDAP"
} else {
    Write-Host "✅ LDAP no está configurado en .env" -ForegroundColor Green
}
```

---

**IMPORTANTE**: Este documento es una guía para **desactivar LDAP en desarrollo local**. En producción, LDAP debe estar **activo** para autenticación con Active Directory de la UNA.

---

**Fecha**: 21 de Diciembre de 2025  
**Autor**: GitHub Copilot
