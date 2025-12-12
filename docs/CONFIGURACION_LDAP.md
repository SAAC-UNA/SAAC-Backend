# Configuración de OpenLDAP para SAAC

## 📋 Resumen

Se configuró un servidor OpenLDAP con Docker para pruebas de autenticación LDAP en el proyecto SAAC. Se importaron los usuarios del equipo (profesores y estudiantes) usando sus datos del `UserSeeder`.

---

## 🐳 Configuración del Contenedor Docker

### Variables de entorno utilizadas:

```yaml
LDAP_ORGANISATION: "UNA"
LDAP_DOMAIN: "una.local"
LDAP_ADMIN_PASSWORD: "admin"
LDAP_CONFIG_PASSWORD: "config"
LDAP_TLS: "false"
```

### Credenciales del administrador:

- **Admin DN:** `cn=admin,dc=una,dc=local`
- **Password:** `admin`

### Puertos expuestos:

- **389:** LDAP (no seguro)
- **636:** LDAPS (SSL/TLS)

---

## 👥 Usuarios Importados

### Estructura de directorios LDAP:

```
dc=una,dc=local
└── ou=users
    ├── ou=profesores
    │   ├── uid=203948609 (Cristopher Montero Jimenez)
    │   └── uid=203849675 (Pablo Castillo Quesada)
    └── ou=estudiantes
        ├── uid=801490957 (Naydelin Nayeli Jiron Castellon)
        ├── uid=208330811 (Jose Andres Jara Arias)
        ├── uid=118620669 (Marisol Hidalgo Murillo)
        ├── uid=207800171 (Ian Enmanuel Villegas Jimenez)
        └── uid=206870079 (Ana Cristina Zuniga Cardenas)
```

### Profesores:

| UID | Nombre | Email |
|-----|--------|-------|
| `203948609` | Cristopher Montero Jimenez | cristopher.montero.jimenez@una.cr |
| `203849675` | Pablo Castillo Quesada | pablo.castillo.quesada@una.cr |

### Estudiantes (Participantes del Proyecto):

| UID (Cédula) | Nombre | Email |
|--------------|--------|-------|
| `801490957` | Naydelin Nayeli Jiron Castellon | nayidelin.jiron.castellon@est.una.ac.cr |
| `208330811` | Jose Andres Jara Arias | jose.jara.arias@est.una.ac.cr |
| `118620669` | Marisol Hidalgo Murillo | marisol.hidalgo.murillo@est.una.ac.cr |
| `207800171` | Ian Enmanuel Villegas Jimenez | ian.villegas.jimenez@est.una.ac.cr |
| `206870079` | Ana Cristina Zuniga Cardenas | ana.zuniga.cardenas@est.una.ac.cr |

**Contraseña para todos los usuarios:** `password123`

---

## 🔧 Configuración en Laravel

### Variables de Entorno (.env)

```env
# ============================================
# LDAP (Autenticación de la Universidad)
# ============================================
LDAP_ENABLED=true
LDAP_HOST=localhost
LDAP_PORT=389
LDAP_BASE_DN=dc=una,dc=local
LDAP_USERNAME=cn=admin,dc=una,dc=local
LDAP_PASSWORD=admin
LDAP_USE_SSL=false
LDAP_USE_TLS=false
LDAP_TIMEOUT=5
```

### Para Producción (NAS de la Universidad)

Cuando tengan acceso al LDAP de la universidad, actualizar:

```env
LDAP_ENABLED=true
LDAP_HOST=ldap.una.ac.cr
LDAP_PORT=389
LDAP_BASE_DN=dc=una,dc=ac,dc=cr
LDAP_USERNAME=cn=readonly,dc=una,dc=ac,dc=cr
LDAP_PASSWORD=contraseña_proporcionada_por_ti
LDAP_USE_SSL=false
LDAP_USE_TLS=true
LDAP_TIMEOUT=10
```

---

## 🔧 Comandos Útiles

### Verificar que el contenedor está corriendo:

```powershell
docker ps | Select-String "ldap"
```

### Listar todos los usuarios:

```powershell
docker exec saac_ldap ldapsearch -x -b "ou=users,dc=una,dc=local" -D "cn=admin,dc=una,dc=local" -w admin "(uid=*)" uid cn mail
```

### Listar solo profesores:

```powershell
docker exec saac_ldap ldapsearch -x -b "ou=profesores,ou=users,dc=una,dc=local" -D "cn=admin,dc=una,dc=local" -w admin "(uid=*)" uid cn mail
```

### Listar solo estudiantes:

```powershell
docker exec saac_ldap ldapsearch -x -b "ou=estudiantes,ou=users,dc=una,dc=local" -D "cn=admin,dc=una,dc=local" -w admin "(uid=*)" uid cn mail
```

### Buscar un usuario específico por cédula:

```powershell
docker exec saac_ldap ldapsearch -x -b "ou=users,dc=una,dc=local" -D "cn=admin,dc=una,dc=local" -w admin "(uid=208330811)"
```

### Ver logs del contenedor:

```powershell
docker logs saac_ldap
```

### Probar autenticación de un usuario:

```powershell
docker exec saac_ldap ldapwhoami -x -D "uid=208330811,ou=estudiantes,ou=users,dc=una,dc=local" -w password123
```

---

## 📁 Archivo LDIF

Se creó el archivo `backend/database/seeders/users.ldif` con todos los usuarios.

### Estructura del archivo:

```ldif
# Unidades organizacionales
ou=users,dc=una,dc=local
ou=profesores,ou=users,dc=una,dc=local
ou=estudiantes,ou=users,dc=una,dc=local

# Usuarios (profesores y estudiantes)
# con atributos: uid, cn, sn, givenName, mail, userPassword
```

---

## 🔄 Cómo Reimportar los Datos

Si necesitan recrear el LDAP desde cero:

```powershell
# 1. Copiar el archivo LDIF al contenedor
docker cp backend/database/seeders/users.ldif saac_ldap:/tmp/users.ldif

# 2. Importar los datos
docker exec saac_ldap ldapadd -x -D "cn=admin,dc=una,dc=local" -w admin -f /tmp/users.ldif
```

---

## 📝 Notas Importantes

### Estructura de los DN (Distinguished Names):

- **Profesores:** `uid={cedula},ou=profesores,ou=users,dc=una,dc=local`
- **Estudiantes:** `uid={cedula},ou=estudiantes,ou=users,dc=una,dc=local`

### Atributos LDAP utilizados:

| Atributo | Descripción | Ejemplo |
|----------|-------------|---------|
| `uid` | User ID (cédula) | `208330811` |
| `cn` | Common Name (nombre completo) | `Jose Andres Jara Arias` |
| `sn` | Surname (apellidos) | `Jara Arias` |
| `givenName` | Nombre(s) | `Jose Andres` |
| `mail` | Email | `jose.jara.arias@est.una.ac.cr` |
| `userPassword` | Contraseña | `password123` |

### Clases de objetos:

- `inetOrgPerson`: Persona con información organizacional
- `posixAccount`: Cuenta de usuario UNIX/Linux
- `shadowAccount`: Información de expiración de contraseña

---

## 🚀 Próximos Pasos

1. ✅ OpenLDAP configurado y funcionando
2. ⏳ Configurar Laravel para autenticación LDAP
3. ⏳ Implementar sincronización de usuarios LDAP con la base de datos
4. ⏳ Probar login con credenciales LDAP
5. ⏳ Configurar roles y permisos basados en grupos LDAP (opcional)

---

## 🆘 Troubleshooting

### El contenedor no inicia:
```powershell
docker logs saac_ldap
```

### No puedo conectarme al LDAP:
Verificar que el puerto 389 no esté en uso:
```powershell
netstat -an | Select-String "389"
```

### Olvidé la contraseña del admin:
Recrear el contenedor con las variables de entorno correctas.

### Necesito agregar más usuarios:
1. Editar el archivo `users.ldif` 
2. Copiarlo al contenedor
3. Ejecutar `ldapadd` como se muestra arriba

---

**Fecha de creación:** Octubre 31, 2025  
**Última actualización:** Noviembre 1, 2025  
**Responsable:** José Jara
