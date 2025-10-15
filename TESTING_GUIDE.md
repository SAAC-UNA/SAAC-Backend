# 📋 COMANDOS ÚTILES PARA TESTING

## 🧪 Ejecutar Pruebas

### Ejecutar todas las pruebas
```bash
php artisan test
```

### Ejecutar con formato legible (recomendado)
```bash
./vendor/bin/phpunit --testdox
```

### Solo pruebas de Universidad (ejemplo funcional)
```bash
php artisan test tests/Feature/UniversityEndpointsTest.php
./vendor/bin/phpunit tests/Feature/UniversityEndpointsTest.php --testdox
```

### Solo pruebas de Evidence Assignment (cuando estén listas)
```bash
php artisan test tests/Feature/EvidenceAssignmentApiTest.php tests/Unit/EvidenceAssignmentServiceTest.php
```

### Ejecutar todas las pruebas de Feature o Unit
```bash
php artisan test tests/Feature
php artisan test tests/Unit
```

### Watcher automático (ejecuta tests al cambiar archivos)
```bash
./vendor/bin/phpunit-watcher watch
```

## 📊 Reportes de Cobertura (REQUIERE DRIVER)

⚠️ **NOTA**: Para generar reportes de cobertura necesitas instalar un driver:

### Instalar PCOV (recomendado, más rápido):
1. Descargar desde: https://pecl.php.net/package/pcov
2. O usar: `pecl install pcov`
3. Agregar `extension=pcov` en php.ini

### Instalar Xdebug (más completo pero más lento):
1. Descargar desde: https://xdebug.org/download
2. Seguir instrucciones de instalación
3. Agregar configuración en php.ini

### Una vez instalado el driver:
```bash
# Reporte HTML completo
php artisan test --coverage-html tests/coverage-html

# Reporte en texto
php artisan test --coverage-text

# Solo cobertura de archivos específicos
./vendor/bin/phpunit tests/Feature/UniversityEndpointsTest.php --coverage-html tests/coverage-html
```

## � Reportes Generados

### Ubicaciones de reportes:
- **HTML Coverage**: `tests/coverage-html/index.html` (solo con driver)
- **Text Coverage**: `tests/coverage.txt` (solo con driver)
- **JUnit XML**: `tests/report.junit.xml` (siempre disponible)

### Abrir reporte HTML en navegador:
```bash
# Windows
start tests/coverage-html/index.html

# Linux/Mac
open tests/coverage-html/index.html
```

## 🎨 Formatos de Salida Disponibles

### Formato TestDox (más legible)
```bash
./vendor/bin/phpunit --testdox
./vendor/bin/phpunit tests/Feature/UniversityEndpointsTest.php --testdox
```

### Formato compacto
```bash
./vendor/bin/phpunit --testdox-summary
```

### Con colores y más verboso
```bash
./vendor/bin/phpunit --colors=always --verbose
```

## 🔧 Configuración de Base de Datos para Tests

En `phpunit.xml` está configurado para usar:
- **Base de datos**: SAAC (misma que desarrollo)
- **Host**: 127.0.0.1:3307
- **Usuario**: root
- **Password**: 12345678

## 📈 Ejemplo de Salida Exitosa

```
University Endpoints (Tests\Feature\UniversityEndpoints)
 ✔ Index devuelve lista de universidades
 ✔ Show devuelve una universidad existente
 ✔ Show devuelve 404 si no existe
 ✔ Store crea una universidad
 ✔ Update actualiza una universidad
 ✔ Destroy elimina una universidad

Tests: 6, Assertions: 14
```

## 🎯 Estado Actual del Proyecto

### ✅ Funcionando:
- **UniversityEndpointsTest**: 6 pruebas pasando
- **Configuración XML**: Correcta
- **Reportes**: JUnit XML disponible
- **Formato**: TestDox funcionando

### 🔄 En progreso:
- **EvidenceAssignmentApiTest**: Necesita configuración de autenticación
- **Cobertura**: Necesita driver (PCOV/Xdebug)

### 📋 Próximos pasos:
1. Instalar driver de cobertura (PCOV recomendado)
2. Arreglar autenticación en tests de Evidence Assignment
3. Agregar más tests de validación y edge cases

## 💡 Tips

- Usa `--testdox` para salida más legible
- Ejecuta tests frecuentemente durante desarrollo
- El formato JUnit XML es útil para CI/CD
- PCOV es más rápido que Xdebug para cobertura
- Tests de Feature requieren base de datos
- Tests Unit pueden funcionar sin BD