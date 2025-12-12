# Configuración de Red y CORS

Este documento explica cómo está configurado el sistema para funcionar en diferentes entornos.

## 🌐 Configuración de CORS (Backend)

**Archivo:** `backend/config/cors.php`

### Desarrollo (local)
- **Permite:** Cualquier origen (`*`)
- **Automático:** Se detecta por `APP_ENV=local`
- **Uso:** Desarrollo local, red local, testing desde celular

### Producción
- **Permite:** Solo dominios específicos de la UNA
- **Configuración:** Variable de entorno `CORS_ALLOWED_ORIGINS`
- **Ejemplo:** `CORS_ALLOWED_ORIGINS=https://saac.una.cr,https://www.saac.una.cr`

## 🔗 Configuración de URLs (Frontend)

**Archivo:** `frontend/src/Config/axios.ts`

El sistema **detecta automáticamente** la URL del backend según el hostname:

### 1. Producción UNA
```
hostname: saac.una.cr o *.una.cr
→ Backend: https://api.saac.una.cr/api
```

### 2. Localhost (desarrollo)
```
hostname: localhost o 127.0.0.1
→ Backend: http://127.0.0.1:8000/api
```

### 3. Red Local (cualquier IP)
```
hostname: 192.168.x.x, 10.x.x.x, 172.x.x.x
→ Backend: http://{misma-ip}:8000/api
```

## 📱 Pruebas desde Celular

### Backend
```bash
# Exponer en toda la red local
php artisan serve --host=0.0.0.0 --port=8000
```

### Frontend
```bash
# Exponer en toda la red local
npm run dev -- --host
```

### Acceso
1. Encuentra tu IP local: `ipconfig` (Windows) o `ifconfig` (Linux/Mac)
2. Desde el celular (misma red WiFi): `http://TU-IP:5173`
3. Ejemplo: `http://192.168.0.3:5173`

## 🚀 Despliegue en Producción

### Backend (.env)
```env
APP_ENV=production
APP_URL=https://api.saac.una.cr
CORS_ALLOWED_ORIGINS=https://saac.una.cr,https://www.saac.una.cr
```

### Frontend
- El sistema detecta automáticamente el dominio de producción
- No requiere cambios de código
- Solo asegurarse de que `saac.una.cr` esté apuntando al frontend
- Y `api.saac.una.cr` al backend

## 🔒 Seguridad

### Desarrollo
- ✅ CORS abierto para facilitar testing
- ✅ Sin HTTPS (HTTP local)
- ⚠️ Solo para desarrollo, **NUNCA** en producción

### Producción
- ✅ CORS restringido a dominios UNA
- ✅ HTTPS obligatorio
- ✅ Rate limiting activo
- ✅ Validación estricta de orígenes

## 📝 Notas Importantes

1. **Timeout aumentado**: El axios está configurado con 60 segundos de timeout para permitir la subida de archivos grandes (hasta 50MB)

2. **Detección automática**: No es necesario cambiar configuraciones manualmente al cambiar de entorno

3. **Variables de entorno**: En producción, asegurarse de configurar `CORS_ALLOWED_ORIGINS` en el `.env`

4. **Testing local**: Para probar desde otros dispositivos en la red local, asegurarse de que el firewall permita conexiones en los puertos 5173 (frontend) y 8000 (backend)
