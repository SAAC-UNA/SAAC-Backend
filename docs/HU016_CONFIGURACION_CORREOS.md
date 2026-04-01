# HU-016: Configuración de Notificaciones por Correo

## 📧 Estado Actual (Desarrollo/Pruebas)

### Configuración de Correo (.env)

Actualmente el sistema está configurado para enviar correos usando **Gmail personal**:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ana.zuniga.cardenas@est.una.ac.cr
MAIL_PASSWORD=****************  # App Password de Gmail (16 caracteres)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="ana.zuniga.cardenas@est.una.ac.cr"
MAIL_FROM_NAME="Sistema SAAC - UNA"
```

### Comportamiento en Desarrollo

**Todos los correos de notificación se envían únicamente a:**
- **Destinatario:** ana.zuniga.cardenas@est.una.ac.cr

Esto permite hacer pruebas sin enviar correos a usuarios reales.

### Código Modificado

**Archivo:** `app/Services/TradicionalExtensionRequestService.php` y `app/Services/FlexibleExtensionRequestService.php`

Ambos servicios usan el mismo patrón:

```php
// Buscar encargados de acreditación de la carrera del proceso
$managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))
    ->when($careerId, fn($q) => $q->whereHas('careers', fn($q2) => $q2->where('carrera_id', $careerId)))
    ->get();

// Fallback: si no hay encargados para esa carrera, se notifica a todos
if ($managers->isEmpty() && $careerId) {
    $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))->get();
}

if ($managers->count() > 0) {
    Notification::send($managers, new ExtensionRequestCreated($solicitud->load('user')));
}
```

> **Nota:** Ya no existe `app/Services/ExtensionRequestService.php`. Fue eliminado en la refactorización SOLID del 31 de marzo, 2026.

---

## � Configuración para Otros Desarrolladores

Como el archivo `.env` **NO se sube al repositorio** (está en `.gitignore`), cada desarrollador debe configurar su propio entorno. Hay **3 opciones**:

### Opción 1: Usar Gmail Personal (Recomendado para Pruebas Reales)

**Paso 1:** Crear App Password de Gmail
1. Ir a https://myaccount.google.com/security
2. Activar "Verificación en 2 pasos" (si no está activa)
3. Ir a https://myaccount.google.com/apppasswords
4. Crear contraseña de aplicación:
   - Seleccionar app: "Correo"
   - Seleccionar dispositivo: "Otro (nombre personalizado)"
   - Nombre: "SAAC Backend Testing"
5. Copiar la contraseña de 16 caracteres (ejemplo: `abcd efgh ijkl mnop`)
6. **IMPORTANTE:** Guardar sin espacios

**Paso 2:** Configurar `.env`
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu.correo@est.una.ac.cr  # Tu correo personal
MAIL_PASSWORD=abcdefghijklmnop         # Sin espacios
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="tu.correo@est.una.ac.cr"
MAIL_FROM_NAME="Sistema SAAC - UNA"
```

**Paso 3:** Limpiar caché
```bash
php artisan config:clear
```

**Paso 4:** Modificar código para recibir correos
Editar `app/Services/ExtensionRequestService.php` (línea ~227):
```php
$testUser = User::where('email', 'tu.correo@est.una.ac.cr')->first();
```

Cambiar `'tu.correo@est.una.ac.cr'` por tu correo personal.

---

### Opción 2: Usar Mailtrap (Recomendado para Ver HTML)

Mailtrap captura todos los correos sin enviarlos realmente. Ideal para ver el formato HTML.

**Paso 1:** Crear cuenta gratuita
1. Ir a https://mailtrap.io
2. Registrarse (gratis)
3. Crear inbox
4. Copiar credenciales SMTP

**Paso 2:** Configurar `.env`
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username_mailtrap
MAIL_PASSWORD=tu_password_mailtrap
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="saac@una.ac.cr"
MAIL_FROM_NAME="Sistema SAAC - UNA"
```

**Paso 3:** Limpiar caché
```bash
php artisan config:clear
```

**Ventaja:** No necesitas modificar código. Todos los correos llegan a tu inbox de Mailtrap.

---

### Opción 3: Modo Log (Solo para Debug)

Los correos se guardan en archivos `.eml` en `storage/logs/` en lugar de enviarse.

**Configurar `.env`:**
```env
MAIL_MAILER=log
```

**Ver correos:**
```bash
# Los archivos .eml se guardan en storage/logs/
# Puedes abrirlos con un visor de correo
```

**Desventaja:** No prueba el envío real de correos, solo el contenido.

---

## �🚀 Cambios para Producción

### 1. Configurar Correo del Servidor UNA

Actualizar el archivo `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.una.ac.cr
MAIL_PORT=587
MAIL_USERNAME=saac@una.ac.cr
MAIL_PASSWORD=***  # Solicitar a IT de la UNA
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="saac@una.ac.cr"
MAIL_FROM_NAME="Sistema SAAC - UNA"
```

**Importante:** Solicitar credenciales SMTP al departamento de IT de la UNA.

### 2. Restaurar Código Original

Reemplazar el código temporal en `app/Services/ExtensionRequestService.php` (líneas ~225-238) con:

```php
// ========== HU-16: NOTIFICACIÓN - INICIO ==========
// Enviar notificación a los encargados de acreditación
try {
    // Obtener la carrera de la solicitud a través de las relaciones
    // evidenceAssignment -> proceso -> accreditationCycle -> careerCampus -> career
    $careerId = $extensionRequest->evidenceAssignment->proceso->accreditationCycle->careerCampus->carrera_id;

    // Buscar encargados de acreditación específicos de esta carrera
    // Esto asegura que solo los encargados relevantes reciban la notificación
    // Ejemplo: Solicitud de Ingeniería → Solo encargados de Ingeniería
    $managers = User::whereHas('roles', function ($query) {
        $query->where('name', 'Encargado de Acreditación');
    })->whereHas('careers', function ($query) use ($careerId) {
        $query->where('carrera_id', $careerId);
    })->get();

    // Fallback: Si no hay encargados específicos para esa carrera,
    // notificar a TODOS los encargados de acreditación (seguridad)
    if ($managers->isEmpty()) {
        Log::warning("No hay encargados específicos para carrera ID {$careerId}, notificando a todos los encargados");
        
        $managers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Encargado de Acreditación');
        })->get();
    }
    
    // Enviar notificación solo si hay encargados
    if ($managers->count() > 0) {
        Notification::send($managers, new ExtensionRequestCreated($extensionRequest));
        Log::info("Notificación enviada a {$managers->count()} encargado(s) de la carrera ID {$careerId}");
    } else {
        Log::warning('No hay usuarios con rol "Encargado de Acreditación" para notificar');
    }
} catch (\Exception $notificationException) {
    // Si falla el envío de emails, no afecta la creación de la solicitud
    // Solo registramos el error en logs
    Log::warning('No se pudo enviar notificación de solicitud de ampliación', [
        'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
        'error' => $notificationException->getMessage()
    ]);
}
// ========== HU-16: NOTIFICACIÓN - FIN ==========
```

### 3. Limpiar Caché

Después de hacer los cambios, ejecutar:

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Verificar Relaciones en Base de Datos

Asegurarse de que:
- Los procesos tienen `ciclo_acreditacion_id` válido
- Los ciclos de acreditación tienen `carrera_sede_id` válido
- Las carreras-sede tienen `carrera_id` válido
- Existen usuarios con rol "Encargado de Acreditación" asignados a las carreras

---

## 🧪 Pruebas en Producción

Antes de desplegar, probar:

1. **Crear solicitud de ampliación** (usuario normal)
2. **Verificar que llegue correo** a los encargados de acreditación
3. **Revisar logs** en `storage/logs/laravel.log`

---

## 📝 Notas Adicionales

### Contenido del Correo

El correo incluye:
- Nombre del solicitante
- Motivo de la solicitud
- Fecha sugerida de ampliación
- Información de la evidencia asignada
- Fecha límite original

### Desactivar Notificaciones

Para desactivar temporalmente las notificaciones sin eliminar código, comentar el bloque completo entre:
```php
// ========== HU-16: NOTIFICACIÓN - INICIO ==========
// ... (comentar todo este bloque)
// ========== HU-16: NOTIFICACIÓN - FIN ==========
```

### Alternativa: Mailtrap para Testing

Si no se desea usar Gmail en desarrollo, se puede usar **Mailtrap** (https://mailtrap.io):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=null
```

Mailtrap captura todos los correos sin enviarlos realmente, ideal para testing.

---

## ⚠️ Seguridad

- **Nunca** commitear el archivo `.env` con contraseñas reales
- Las App Passwords de Gmail deben mantenerse privadas
- En producción, usar variables de entorno del servidor o gestores de secretos

---

**Fecha de actualización:** 24 de enero de 2026  
**Responsable:** Equipo SAAC-UNA
