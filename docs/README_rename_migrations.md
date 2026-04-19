# Renombrado de Migraciones: Español → Inglés

**Commit:** `refactor: rename migration files from Spanish to English naming convention`  
**Rama:** `Hu_028_Publicación-de-informe-de-acreditación-aprobado`  
**Archivos afectados:** 30 migraciones en `database/migrations/`

---

## ¿Qué pasó?

Se renombraron 30 archivos de migración para seguir el estándar de nombres en inglés.  
Los archivos cambiaron de nombre pero su **contenido no fue modificado**.

---

## ¿Qué debo hacer al jalar este commit?

### Caso A — Ya tenías migraciones corridas (lo más común)

Tu tabla `migrations` en la BD local aún tiene los nombres en español.  
Laravel verá los nuevos nombres como migraciones nuevas y fallará al intentar correrlas.

**Pasos obligatorios:**

```bash
# 1. Jalar el commit
git pull

# 2. Actualizar los nombres en tu BD local
php database/scripts/rename_migrations_spanish_to_english.php

# 3. Correr migraciones pendientes reales
php artisan migrate
```

---

### Caso B — BD nueva / nunca corriste migraciones

No necesitas el script. Solo:

```bash
git pull
php artisan migrate
```

---

## Verificación

Después de correr el script, comprueba que no haya migraciones inesperadas:

```bash
php artisan migrate:status
```

No debe aparecer ninguna migración como `Pending` excepto las que ya estaban pendientes antes.

---

## Sobre el script

`database/scripts/rename_migrations_spanish_to_english.php` — actualiza los 30 registros en la tabla `migrations` con los nuevos nombres en inglés.  
Es seguro correrlo varias veces: si un nombre ya fue actualizado, lo marca como `SKIP` sin romper nada.
