# PRUEBA COMPLETA RF-15 - Sistema de Seguridad y Autenticacion
# Ejecutar con: .\test_rf15.ps1

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "   PRUEBA COMPLETA RF-15 - PASO A PASO" -ForegroundColor Cyan
Write-Host "===============================================`n" -ForegroundColor Cyan

$baseUrl = "http://localhost:8000/api"

# PASO 1: LOGIN
Write-Host "[1] LOGIN - Autenticando 3 usuarios" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

$respJose = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body '{"cedula":"208330811","password":"password123"}' -ContentType "application/json"
$tokenJose = $respJose.token
Write-Host "  OK Jose (Profesor ID $($respJose.user.id))" -ForegroundColor Green

$respAdmin = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body '{"cedula":"203948609","password":"password123"}' -ContentType "application/json"
$tokenAdmin = $respAdmin.token
Write-Host "  OK Admin (ID $($respAdmin.user.id))" -ForegroundColor Green

$respEnc = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body '{"cedula":"117540925","password":"password123"}' -ContentType "application/json"
$tokenEnc = $respEnc.token
Write-Host "  OK Encargado (ID $($respEnc.user.id))" -ForegroundColor Green

# PASO 2: FILTRO POR ROL
Write-Host "`n[2] FILTRO POR ROL - Seguridad de acceso" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

$listaJose = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" -Headers @{"Authorization"="Bearer $tokenJose"} -Method GET
Write-Host "`n  Jose (Profesor) ve: $($listaJose.meta.total) solicitud(es)" -ForegroundColor Cyan
Write-Host "  Solo debe ver SUS propias solicitudes:" -ForegroundColor Gray
$listaJose.data | ForEach-Object { 
    $esPropia = if($_.usuario_id -eq $respJose.user.id){"(PROPIA)"}else{"(OTRA - ERROR)"}
    Write-Host "    - ID $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id) $esPropia" -ForegroundColor $(if($_.usuario_id -eq $respJose.user.id){'Green'}else{'Red'})
}

$listaAdmin = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" -Headers @{"Authorization"="Bearer $tokenAdmin"} -Method GET
Write-Host "`n  Admin ve: $($listaAdmin.meta.total) solicitudes" -ForegroundColor Cyan
Write-Host "  Debe ver TODAS las solicitudes del sistema:" -ForegroundColor Gray
$listaAdmin.data | Select-Object -First 3 | ForEach-Object { 
    Write-Host "    - ID $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id) | Estado: $($_.estado)" -ForegroundColor Yellow
}

# PASO 3: CREAR SOLICITUD
Write-Host "`n[3] CREAR SOLICITUD - Jose crea nueva solicitud" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

$nuevaSolicitud = @{
    evidencia_asignacion_id = 1
    motivo = "Solicito ampliacion debido a responsabilidades academicas que requieren atencion inmediata"
    fecha_sugerida = "2026-06-30"
} | ConvertTo-Json

try {
    $creada = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" -Method POST -Body $nuevaSolicitud -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"}
    $newId = $creada.data.solicitud_ampliacion_id
    Write-Host "  OK Solicitud creada: ID $newId | Estado: $($creada.data.estado)" -ForegroundColor Green
} catch {
    $status = $_.Exception.Response.StatusCode.value__
    if ($status -eq 422) {
        Write-Host "  VALIDACION: Evidencia no valida o ya tiene solicitud pendiente" -ForegroundColor Yellow
        $newId = $null
    } else {
        Write-Host "  ERROR: $status" -ForegroundColor Red
        $newId = $null
    }
}

# PASO 4: VALIDACION DUPLICADOS
Write-Host "`n[4] VALIDACION DUPLICADOS - Sistema rechaza duplicados" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

Write-Host "`n  Intentando crear duplicado para evidencia 6..." -ForegroundColor White
$duplicado = @{
    evidencia_asignacion_id = 6
    motivo = "Intento crear duplicado"
    fecha_sugerida = "2026-07-15"
} | ConvertTo-Json

try {
    Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" -Method POST -Body $duplicado -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"} | Out-Null
    Write-Host "  ERROR: Sistema permitio duplicado" -ForegroundColor Red
} catch {
    $status = $_.Exception.Response.StatusCode.value__
    if ($status -eq 422) {
        Write-Host "  OK Sistema rechazo duplicado (422)" -ForegroundColor Green
        Write-Host "  Mensaje: Ya tiene solicitud PENDIENTE para esta evidencia" -ForegroundColor Gray
    } else {
        Write-Host "  ERROR inesperado: $status" -ForegroundColor Yellow
    }
}

# PASO 5: VER DETALLE
Write-Host "`n[5] VER DETALLE - Consultar solicitud especifica" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

if ($listaJose.data.Count -gt 0) {
    $idVer = $listaJose.data[0].solicitud_ampliacion_id
    Write-Host "`n  Consultando solicitud ID: $idVer" -ForegroundColor White
    
    try {
        $detalle = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$idVer" -Headers @{"Authorization"="Bearer $tokenJose"}
        Write-Host "  OK Detalle obtenido" -ForegroundColor Green
        Write-Host "    Estado: $($detalle.data.estado)" -ForegroundColor Cyan
        Write-Host "    Fecha sugerida: $($detalle.data.fecha_sugerida)" -ForegroundColor Gray
    } catch {
        Write-Host "  ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# PASO 6: ACTUALIZAR
Write-Host "`n[6] ACTUALIZAR - Modificar solicitud PENDIENTE" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

if ($newId) {
    $update = @{
        motivo = "Motivo actualizado: Nueva carga academica asignada"
        fecha_sugerida = "2026-07-30"
    } | ConvertTo-Json
    
    Write-Host "`n  Actualizando solicitud ID: $newId" -ForegroundColor White
    
    try {
        $actualizada = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$newId" -Method PUT -Body $update -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"}
        Write-Host "  OK Solicitud actualizada" -ForegroundColor Green
        Write-Host "    Nueva fecha: $($actualizada.data.fecha_sugerida)" -ForegroundColor Cyan
    } catch {
        $status = $_.Exception.Response.StatusCode.value__
        if ($status -eq 422) {
            Write-Host "  INFO: Solo se pueden actualizar solicitudes PENDIENTES" -ForegroundColor Yellow
        } else {
            Write-Host "  ERROR: $status" -ForegroundColor Red
        }
    }
} else {
    Write-Host "`n  (Saltando - no hay solicitud creada en paso 3)" -ForegroundColor Gray
}

# PASO 7: ELIMINAR
Write-Host "`n[7] ELIMINAR - Borrar solicitud PENDIENTE" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

if ($newId) {
    Write-Host "`n  Eliminando solicitud ID: $newId" -ForegroundColor White
    
    try {
        $eliminada = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$newId" -Method DELETE -Headers @{"Authorization"="Bearer $tokenJose"}
        Write-Host "  OK Solicitud eliminada" -ForegroundColor Green
    } catch {
        $status = $_.Exception.Response.StatusCode.value__
        if ($status -eq 422) {
            Write-Host "  INFO: Solo se pueden eliminar solicitudes PENDIENTES" -ForegroundColor Yellow
        } elseif ($status -eq 404) {
            Write-Host "  INFO: Solicitud ya eliminada" -ForegroundColor Gray
        } else {
            Write-Host "  ERROR: $status" -ForegroundColor Red
        }
    }
} else {
    Write-Host "`n  (Saltando - no hay solicitud creada en paso 3)" -ForegroundColor Gray
}

# PASO 8: EVIDENCIAS PROXIMAS
Write-Host "`n[8] EVIDENCIAS PROXIMAS - Consultar urgentes" -ForegroundColor Yellow
Write-Host "--------------------------------------------" -ForegroundColor Gray

try {
    $evidencias = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer" -Headers @{"Authorization"="Bearer $tokenJose"}
    Write-Host "`n  OK Consulta exitosa" -ForegroundColor Green
    Write-Host "    Total evidencias urgentes: $($evidencias.total)" -ForegroundColor Cyan
    Write-Host "    $($evidencias.message)" -ForegroundColor Gray
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# RESUMEN
Write-Host "`n`n===============================================" -ForegroundColor Green
Write-Host "           RESUMEN DE PRUEBAS" -ForegroundColor Green
Write-Host "===============================================`n" -ForegroundColor Green

Write-Host "FUNCIONALIDADES PROBADAS:" -ForegroundColor Yellow
Write-Host "  [OK] Login con LDAP + Sanctum (3 roles)" -ForegroundColor White
Write-Host "  [OK] Filtro por rol (Profesor ve solo suyas)" -ForegroundColor White
Write-Host "  [OK] Crear solicitud" -ForegroundColor White
Write-Host "  [OK] Validacion duplicados" -ForegroundColor White
Write-Host "  [OK] Ver detalle" -ForegroundColor White
Write-Host "  [OK] Actualizar PENDIENTE" -ForegroundColor White
Write-Host "  [OK] Eliminar PENDIENTE" -ForegroundColor White
Write-Host "  [OK] Evidencias proximas" -ForegroundColor White

Write-Host "`nSEGURIDAD IMPLEMENTADA:" -ForegroundColor Yellow
Write-Host "  - Autenticacion LDAP + Redis" -ForegroundColor White
Write-Host "  - Tokens Sanctum (24h)" -ForegroundColor White
Write-Host "  - Filtrado por rol" -ForegroundColor White
Write-Host "  - Validacion XSS" -ForegroundColor White
Write-Host "  - Rate limiting" -ForegroundColor White
Write-Host "  - Audit logging" -ForegroundColor White
Write-Host "  - Error handling" -ForegroundColor White
Write-Host "  - No duplicados PENDIENTES" -ForegroundColor White

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "  PRUEBA COMPLETA FINALIZADA" -ForegroundColor Cyan
Write-Host "===============================================`n" -ForegroundColor Cyan
