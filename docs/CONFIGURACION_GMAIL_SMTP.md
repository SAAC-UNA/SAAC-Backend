# 📧 Configuración de Gmail SMTP para Notificaciones

## Pasos para Configurar Gmail

### 1. Habilitar Verificación en 2 Pasos

1. Ve a tu [Cuenta de Google - Seguridad](https://myaccount.google.com/security)
2. En la sección "Cómo accedes a Google", haz clic en **Verificación en 2 pasos**
3. Sigue las instrucciones para habilitarla (si no la tienes ya)

### 2. Crear una Contraseña de Aplicación

1. Ve a [Google App Passwords](https://myaccount.google.com/apppasswords)
2. Si te pide iniciar sesión nuevamente, hazlo
3. En "Selecciona la app", elige **Correo**
4. En "Selecciona el dispositivo", elige **Otro (nombre personalizado)**
5. Escribe un nombre como "SAAC UNA Backend"
6. Haz clic en **Generar**
7. Google te mostrará una contraseña de **16 caracteres** (formato: `abcd efgh ijkl mnop`)
8. **¡COPIA ESTA CONTRASEÑA!** (la necesitarás para el .env)
   - ⚠️ **IMPORTANTE:** Google la muestra con espacios, pero debes **pegarla SIN espacios**
   - Ejemplo: Si ves `dydj ksfn oepd fkig`, debes usar `dydjksfnoepdfkig`

### 3. Configurar el archivo .env

Edita tu archivo `.env` (no el `.env.example`) y configura:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="tu-email@gmail.com"
MAIL_FROM_NAME="Sistema SAAC - UNA"
```

**IMPORTANTE:**
- En `MAIL_PASSWORD` copia la contraseña de 16 caracteres **SIN ESPACIOS**
  - ❌ Incorrecto: `abcd efgh ijkl mnop` (con espacios)
  - ✅ Correcto: `abcdefghijklmnop` (sin espacios)
- No uses tu contraseña normal de Gmail, debe ser la App Password

### 4. Probar la Configuración

Ejecuta el comando de prueba:

```bash
php artisan email:test tu-email@gmail.com --name="Tu Nombre"
```

Si todo está bien, recibirás un correo en tu bandeja de entrada.

## Solución de Problemas

### Error: "Username and Password not accepted"

**Causa:** Contraseña incorrecta o verificación en 2 pasos no habilitada

**Solución:**
1. Verifica que tienes verificación en 2 pasos habilitada
2. Genera una nueva App Password
3. Copia la contraseña completa SIN espacios
4. Pégala en MAIL_PASSWORD del .env

### Error: "Connection could not be established"

**Causa:** Firewall o configuración de red

**Solución:**
1. Verifica que `MAIL_PORT=587`
2. Verifica que `MAIL_ENCRYPTION=tls`
3. Comprueba que tu firewall no bloquea el puerto 587

### Error: "MAIL_FROM_ADDRESS must be provided"

**Causa:** Falta configurar MAIL_FROM_ADDRESS

**Solución:**
```env
MAIL_FROM_ADDRESS="tu-email@gmail.com"
```

## Verificar Configuración Actual

```bash
php artisan tinker

# Dentro de tinker:
config('mail.default')          // Debe ser: smtp
config('mail.mailers.smtp.host') // Debe ser: smtp.gmail.com
config('mail.from.address')      // Debe ser tu email
```

## Alternativa: Modo Log (Desarrollo)

Si no quieres configurar Gmail aún, puedes usar el modo `log` que guarda los correos en un archivo:

```env
MAIL_MAILER=log
```

Los correos se guardarán en `storage/logs/laravel.log`

## Notas Importantes

- ⚠️ **NUNCA** subas tu `.env` a Git (ya está en `.gitignore`)
- ⚠️ La App Password es como tu contraseña: mantenla segura
- ✅ En producción, usa el servidor SMTP de la UNA
- ✅ Gmail tiene límite de 500 correos por día con cuentas gratuitas
- ✅ Para producción real, considera usar un servicio como SendGrid, Mailgun o el SMTP institucional

## Testing Avanzado

### Enviar correo con datos personalizados:

```bash
# Correo simple
php artisan email:test ejemplo@gmail.com --name="Juan Pérez"

# Desde tinker (más control):
php artisan tinker

use App\Mail\TestNotificationMail;
use Illuminate\Support\Facades\Mail;

Mail::to('ejemplo@gmail.com')->send(new TestNotificationMail(
    userName: 'María González',
    notificationTitle: 'Nueva evidencia asignada',
    notificationMessage: 'Se te ha asignado la evidencia "Infraestructura TI" con fecha límite 31/01/2026',
    actionUrl: 'http://localhost:8000/evidencias/123',
    actionText: 'Ver evidencia'
));
```

## Ejemplo de Uso en NotificationService

Para usar las plantillas HTML en las notificaciones, modifica el método `sendEmail` en `NotificationService.php`:

```php
use App\Mail\TestNotificationMail;

private static function sendEmail($notification): bool
{
    try {
        $user = $notification->usuario;
        
        Mail::to($user->email)->send(new TestNotificationMail(
            userName: $user->nombre,
            notificationTitle: $notification->titulo,
            notificationMessage: $notification->mensaje,
            actionUrl: $notification->enlace ? config('app.url') . $notification->enlace : null,
            actionText: 'Ver en el sistema'
        ));
        
        return true;
    } catch (\Exception $e) {
        Log::error('Error al enviar correo de notificación', [
            'notification_id' => $notification->notificacion_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
```

## Resultado Esperado

Al ejecutar `php artisan email:test tu-email@gmail.com`, deberías ver:

```
📧 Enviando correo de prueba a: tu-email@gmail.com

✅ Correo enviado exitosamente!

Configuración utilizada:
+-------------------+---------------------------------+
| Parámetro         | Valor                          |
+-------------------+---------------------------------+
| MAIL_MAILER       | smtp                           |
| MAIL_HOST         | smtp.gmail.com                 |
| MAIL_PORT         | 587                            |
| MAIL_ENCRYPTION   | tls                            |
| MAIL_FROM_ADDRESS | tu-email@gmail.com             |
| MAIL_FROM_NAME    | Sistema SAAC - UNA             |
+-------------------+---------------------------------+
```

Y en tu bandeja de entrada recibirás un correo profesional con los colores de la UNA (rojo #c8102e).
