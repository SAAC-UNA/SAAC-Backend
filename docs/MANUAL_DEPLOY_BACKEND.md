# SAAC Backend — Guia de Despliegue en Produccion

**Sistema de Acreditacion y Autoevaluacion de Carreras**  
Universidad Nacional de Costa Rica — Grupo 03-2025

---

## 1. Requisitos del Servidor / Entorno

| Herramienta           | Version minima recomendada | Notas                                                                               |
| --------------------- | -------------------------- | ----------------------------------------------------------------------------------- |
| **Sistema operativo** | Ubuntu Server `22.04+`     | Puede adaptarse a Debian/RHEL                                                       |
| **PHP**               | `>= 8.2`                   | Con extensiones `mbstring`, `xml`, `curl`, `zip`, `mysql`, `bcmath`, `intl`, `ldap` |
| **OPcache**           | Habilitado en produccion   | Mejora rendimiento y reduce tiempo de respuesta PHP                                 |
| **Composer**          | `>= 2.x`                   | Gestor de dependencias PHP                                                          |
| **Apache**            | `>= 2.4`                   | Requiere `mod_rewrite` habilitado                                                   |
| **MySQL**             | `>= 8.x`                   | Motor principal de base de datos                                                    |
| **LDAP**              | Acceso institucional       | Solo si `LDAP_ENABLED=true`                                                         |

> **Importante:** A diferencia del frontend, el backend requiere servicios en ejecucion (PHP + BD + opcional LDAP). No es un despliegue estatico.

---

## 2. Variables de Entorno

Crear `.env` basado en `.env.example` y ajustar a produccion:

```env
APP_NAME=SAAC-UNA
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tu-dominio
FRONTEND_URL=https://tu-frontend

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saac_db
DB_USERNAME=saac_user
DB_PASSWORD=********

CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database

LDAP_ENABLED=true
LDAP_HOST=<host_ldap>
LDAP_PORT=389
LDAP_BASE_DN=<base_dn>
LDAP_USERNAME=<usuario_lectura>
LDAP_PASSWORD=<password_ldap>
LDAP_USE_SSL=false
LDAP_USE_TLS=true
```

> En produccion, nunca dejar credenciales de ejemplo ni `APP_DEBUG=true`.

---

## 3. Dependencias del Proyecto (`composer install`)

Al ejecutar `composer install` se instalan las dependencias definidas en `composer.json`.

### 3.1 Dependencias de produccion (runtime)

| Paquete                            | Version | Descripcion                               |
| ---------------------------------- | ------- | ----------------------------------------- |
| `laravel/framework`                | `^12.0` | Framework principal                       |
| `laravel/sanctum`                  | `^4.2`  | Autenticacion por tokens                  |
| `spatie/laravel-permission`        | `^6.21` | Roles y permisos                          |
| `directorytree/ldaprecord-laravel` | `^3.4`  | Integracion LDAP para login institucional |
| `barryvdh/laravel-dompdf`          | `^3.1`  | Generacion de PDFs                        |
| `phpoffice/phpspreadsheet`         | `^5.3`  | Exportaciones/importaciones Excel         |

### 3.2 Dependencias de desarrollo (no obligatorias en servidor)

| Paquete             | Version | Descripcion                   |
| ------------------- | ------- | ----------------------------- |
| `phpunit/phpunit`   | `^12.5` | Pruebas automatizadas         |
| `laravel/pint`      | `^1.26` | Formateo de codigo            |
| `laravel/telescope` | `^5.18` | Observabilidad en desarrollo  |
| `brianium/paratest` | `^7.16` | Ejecucion paralela de pruebas |

---

## 4. Pasos para Instalar y Desplegar

```bash
# 1. Instalar paquetes del servidor
sudo apt update
sudo apt install -y apache2 mysql-server unzip git
sudo apt install -y php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-ldap libapache2-mod-php8.2

# 2. Habilitar rewrite en Apache
sudo a2enmod rewrite
sudo systemctl restart apache2

# 3. Clonar proyecto
cd /var/www
sudo git clone <URL_DEL_REPOSITORIO> saac-backend
cd saac-backend

# 4. Instalar dependencias de produccion
composer install --no-dev --optimize-autoloader

# 5. Configurar entorno
cp .env.example .env
php artisan key:generate
# Editar .env con valores reales de produccion (ver seccion 2)

# 6. Migraciones y seeders
php artisan migrate --force
php artisan db:seed --force

# 7. Optimizar Laravel
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Permisos recomendados:

```bash
sudo chown -R www-data:www-data /var/www/saac-backend
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;
sudo find bootstrap/cache -type d -exec chmod 775 {} \;
sudo find bootstrap/cache -type f -exec chmod 664 {} \;
```

---

## 5. Configuracion del Servidor Web (Apache)

Crear `/etc/apache2/sites-available/saac-backend.conf`:

```apache
<VirtualHost *:80>
  ServerName api.tu-dominio
  DocumentRoot /var/www/saac-backend/public

  <Directory /var/www/saac-backend/public>
    AllowOverride All
    Require all granted
  </Directory>

  ErrorLog ${APACHE_LOG_DIR}/saac-backend-error.log
  CustomLog ${APACHE_LOG_DIR}/saac-backend-access.log combined
</VirtualHost>
```

Activar el sitio:

```bash
sudo a2ensite saac-backend.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

> Verificar que el `DocumentRoot` apunte a `public/` y no a la raiz del proyecto.

---

## 6. Procesos de Fondo (colas y scheduler)

### 6.1 Worker de colas (systemd)

Crear `/etc/systemd/system/saac-queue.service`:

```ini
[Unit]
Description=SAAC Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/saac-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/saac-backend

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now saac-queue.service
```

### 6.2 Scheduler de Laravel (cron)

```bash
crontab -e
```

Agregar:

```cron
* * * * * cd /var/www/saac-backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Scripts/Comandos Operativos Utiles

| Comando                       | Descripcion                         |
| ----------------------------- | ----------------------------------- |
| `php artisan migrate --force` | Aplica migraciones en produccion    |
| `php artisan db:seed --force` | Carga datos semilla en produccion   |
| `php artisan optimize`        | Optimiza caches del framework       |
| `php artisan queue:work`      | Ejecuta worker de colas manualmente |
| `php artisan schedule:run`    | Ejecuta tareas programadas una vez  |
| `php artisan migrate:status`  | Verifica estado de migraciones      |

---

## 8. Verificacion Rapida Post-Despliegue

1. Verificar respuesta HTTP del backend: `curl -I http://api.tu-dominio`.
2. Confirmar estado de migraciones: `php artisan migrate:status`.
3. Validar login LDAP con una cuenta real (si aplica).
4. Revisar logs de aplicacion y servidor:
    - `storage/logs/laravel.log`
    - `/var/log/apache2/saac-backend-error.log`
5. Confirmar servicios activos:
    - `sudo systemctl status saac-queue.service`
    - `sudo systemctl status apache2`
6. Confirmar OPcache activo: `php -m` debe mostrar `Zend OPcache`.

---

## 9. Actualizacion de Version (flujo rapido)

```bash
cd /var/www/saac-backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
sudo systemctl restart saac-queue.service
sudo systemctl reload apache2
```

---

## 10. Checklist de Salida a Produccion

1. `APP_ENV=production` y `APP_DEBUG=false`.
2. Secretos reales en `.env` (sin valores de ejemplo).
3. Migraciones ejecutadas sin errores.
4. Cola (`saac-queue.service`) y scheduler funcionando.
5. Logs monitoreados y rotacion configurada.
6. Backups de base de datos habilitados.

---

_Generado el 8 de abril de 2026 — SAAC Backend v1.0.0_
