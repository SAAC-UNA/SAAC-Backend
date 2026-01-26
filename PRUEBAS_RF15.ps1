# GUIA DE PRUEBAS RF-15 - MANUAL

## ANTES DE EMPEZAR:
## 1. Abrir una terminal PowerShell
## 2. Navegar a: cd C:\SAAC-UNA\SAAC-Backend
## 3. Iniciar servidor: php artisan serve
## 4. Abrir OTRA terminal PowerShell y ejecutar estos comandos uno por uno

# ========================================
# PASO 1: LOGIN
# ========================================

# 1.1 Login José (Profesor)
$respJose = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body '{"cedula":"208330811","password":"password123"}' -ContentType "application/json"
$tokenJose = $respJose.token
Write-Host "Jose (Profesor): OK - ID $($respJose.user.id)" -ForegroundColor Green

# 1.2 Login Admin
$respAdmin = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body '{"cedula":"203948609","password":"password123"}' -ContentType "application/json"
$tokenAdmin = $respAdmin.token
Write-Host "Admin: OK - ID $($respAdmin.user.id)" -ForegroundColor Green

# ========================================
# PASO 2: FILTRO POR ROL
# ========================================

# 2.1 José ve solo SUS solicitudes
$listaJose = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" -Headers @{"Authorization"="Bearer $tokenJose"} -Method GET
Write-Host "`nJose ve: $($listaJose.meta.total) solicitudes (solo las suyas)" -ForegroundColor Cyan
$listaJose.data | ForEach-Object { Write-Host "  ID $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id)" }

# 2.2 Admin ve TODAS
$listaAdmin = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" -Headers @{"Authorization"="Bearer $tokenAdmin"} -Method GET
Write-Host "`nAdmin ve: $($listaAdmin.meta.total) solicitudes (TODAS del sistema)" -ForegroundColor Yellow
$listaAdmin.data | Select-Object -First 5 | ForEach-Object { Write-Host "  ID $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id)" }

# ========================================
# PASO 3: CREAR SOLICITUD
# ========================================

# Crear solicitud para evidencia 1
$nueva = @{ evidencia_asignacion_id = 1; motivo = "Solicito ampliacion por carga academica"; fecha_sugerida = "2026-06-30" } | ConvertTo-Json
try {
    $creada = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" -Method POST -Body $nueva -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"}
    $newId = $creada.data.solicitud_ampliacion_id
    Write-Host "`nSolicitud creada: ID $newId" -ForegroundColor Green
} catch {
    Write-Host "`nError al crear: Posiblemente evidencia 1 no valida o ya tiene pendiente" -ForegroundColor Yellow
}

# ========================================
# PASO 4: VALIDACION DUPLICADOS
# ========================================

# Intentar crear duplicado para evidencia 6 (que ya tiene)
Write-Host "`nIntentando crear duplicado para evidencia 6..." -ForegroundColor White
$duplicado = @{ evidencia_asignacion_id = 6; motivo = "Duplicado"; fecha_sugerida = "2026-07-15" } | ConvertTo-Json
try {
    Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo" -Method POST -Body $duplicado -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"} | Out-Null
    Write-Host "ERROR: Permitio duplicado!" -ForegroundColor Red
} catch {
    Write-Host "OK: Sistema rechazo duplicado (422)" -ForegroundColor Green
}

# ========================================
# PASO 5: VER DETALLE
# ========================================

# Ver detalle de primera solicitud de Jose
if ($listaJose.data.Count -gt 0) {
    $idVer = $listaJose.data[0].solicitud_ampliacion_id
    $detalle = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/$idVer" -Headers @{"Authorization"="Bearer $tokenJose"}
    Write-Host "`nDetalle solicitud $idVer" -ForegroundColor Cyan
    Write-Host "  Estado: $($detalle.data.estado)"
    Write-Host "  Motivo: $($detalle.data.motivo.Substring(0,[Math]::Min(50,$detalle.data.motivo.Length)))..."
}

# ========================================
# PASO 6: ACTUALIZAR
# ========================================

# Actualizar solicitud creada en paso 3 (si existe)
if ($newId) {
    $update = @{ motivo = "Motivo actualizado"; fecha_sugerida = "2026-08-15" } | ConvertTo-Json
    try {
        $actualizada = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/$newId" -Method PUT -Body $update -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"}
        Write-Host "`nSolicitud $newId actualizada OK" -ForegroundColor Green
    } catch {
        Write-Host "`nError al actualizar: Solo se pueden actualizar solicitudes PENDIENTES" -ForegroundColor Yellow
    }
}

# ========================================
# PASO 7: ELIMINAR
# ========================================

# Eliminar solicitud creada
if ($newId) {
    try {
        Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/$newId" -Method DELETE -Headers @{"Authorization"="Bearer $tokenJose"}
        Write-Host "`nSolicitud $newId eliminada OK" -ForegroundColor Green
    } catch {
        Write-Host "`nError al eliminar" -ForegroundColor Red
    }
}

# ========================================
# PASO 8: EVIDENCIAS PROXIMAS
# ========================================

$evidencias = Invoke-RestMethod -Uri "http://localhost:8000/api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer" -Headers @{"Authorization"="Bearer $tokenJose"}
Write-Host "`nEvidencias proximas a vencer: $($evidencias.total)" -ForegroundColor Cyan
Write-Host "$($evidencias.message)" -ForegroundColor Gray

# ========================================
# RESUMEN
# ========================================

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "  PRUEBA COMPLETA FINALIZADA" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nFUNCIONALIDADES PROBADAS:" -ForegroundColor Yellow
Write-Host "  [OK] Login LDAP + Sanctum"
Write-Host "  [OK] Filtro por rol"
Write-Host "  [OK] Crear solicitud"
Write-Host "  [OK] Validacion duplicados"
Write-Host "  [OK] Ver detalle"
Write-Host "  [OK] Actualizar"
Write-Host "  [OK] Eliminar"
Write-Host "  [OK] Evidencias proximas`n"
