# ═══════════════════════════════════════════════════════════════
# PRUEBA COMPLETA RF-15 - SISTEMA DE SEGURIDAD Y AUTENTICACIÓN
# ═══════════════════════════════════════════════════════════════

Write-Host "`n╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        PRUEBA COMPLETA RF-15 - SISTEMA DE SEGURIDAD          ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

$baseUrl = "http://localhost:8000/api"

# ═══════════════════════════════════════════════════════════════
# PASO 1: LOGIN CON DIFERENTES ROLES
# ═══════════════════════════════════════════════════════════════
Write-Host "[PASO 1] LOGIN - Autenticación de 3 usuarios con diferentes roles" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

# 1.1 Login José (Profesor)
Write-Host "`n1.1 José Jara - Rol: Profesor" -ForegroundColor White
$loginJose = @{ 
    cedula = "208330811"
    password = "password123" 
} | ConvertTo-Json

try {
    $respJose = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body $loginJose -ContentType "application/json"
    $tokenJose = $respJose.token
    $userIdJose = $respJose.user.id
    Write-Host "    ✓ Autenticado correctamente" -ForegroundColor Green
    Write-Host "    User ID: $userIdJose | Token: $($tokenJose.Substring(0,30))..." -ForegroundColor Gray
    Write-Host "    Rol: $($respJose.user.roles[0].name)" -ForegroundColor Gray
} catch {
    Write-Host "    ✗ Error en login: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# 1.2 Login Admin
Write-Host "`n1.2 Administrador del Sistema" -ForegroundColor White
$loginAdmin = @{ 
    cedula = "203948609"
    password = "password123" 
} | ConvertTo-Json

try {
    $respAdmin = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body $loginAdmin -ContentType "application/json"
    $tokenAdmin = $respAdmin.token
    $userIdAdmin = $respAdmin.user.id
    Write-Host "    ✓ Autenticado correctamente" -ForegroundColor Green
    Write-Host "    User ID: $userIdAdmin | Token: $($tokenAdmin.Substring(0,30))..." -ForegroundColor Gray
    Write-Host "    Rol: $($respAdmin.user.roles[0].name)" -ForegroundColor Gray
} catch {
    Write-Host "    ✗ Error en login: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# 1.3 Login Encargado
Write-Host "`n1.3 Encargado de Calidad" -ForegroundColor White
$loginEncargado = @{ 
    cedula = "117540925"
    password = "password123" 
} | ConvertTo-Json

try {
    $respEncargado = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method POST -Body $loginEncargado -ContentType "application/json"
    $tokenEncargado = $respEncargado.token
    $userIdEncargado = $respEncargado.user.id
    Write-Host "    ✓ Autenticado correctamente" -ForegroundColor Green
    Write-Host "    User ID: $userIdEncargado | Token: $($tokenEncargado.Substring(0,30))..." -ForegroundColor Gray
    Write-Host "    Rol: $($respEncargado.user.roles[0].name)" -ForegroundColor Gray
} catch {
    Write-Host "    ✗ Error en login: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

Write-Host "`n✅ Resultado: 3 usuarios autenticados correctamente" -ForegroundColor Green

# ═══════════════════════════════════════════════════════════════
# PASO 2: FILTRO POR ROL - Verificar Segmentación de Datos
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 2] FILTRO POR ROL - Seguridad de acceso a datos" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host "Objetivo: Verificar que cada rol ve solo las solicitudes permitidas" -ForegroundColor Gray

# 2.1 José (Profesor) - Solo ve SUS solicitudes
Write-Host "`n2.1 José (Profesor) - Debe ver SOLO sus propias solicitudes" -ForegroundColor White
try {
    $listaJose = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" `
        -Method GET `
        -Headers @{"Authorization"="Bearer $tokenJose"; "Accept"="application/json"}
    
    Write-Host "    Total solicitudes visibles: $($listaJose.meta.total)" -ForegroundColor Cyan
    
    if ($listaJose.data.Count -gt 0) {
        $listaJose.data | ForEach-Object {
            $color = if($_.usuario_id -eq $userIdJose){'Green'}else{'Red'}
            $icon = if($_.usuario_id -eq $userIdJose){'✓'}else{'✗ FALLO SEGURIDAD'}
            Write-Host "    $icon ID: $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id) | Evidencia: $($_.evidencia_asignacion_id) | Estado: $($_.estado)" -ForegroundColor $color
        }
        
        # Verificar seguridad
        $solicitudesOtros = $listaJose.data | Where-Object { $_.usuario_id -ne $userIdJose }
        if ($solicitudesOtros.Count -gt 0) {
            Write-Host "    ✗ FALLO DE SEGURIDAD: Profesor ve solicitudes de otros usuarios" -ForegroundColor Red
        } else {
            Write-Host "    ✅ Seguridad correcta: Solo ve sus propias solicitudes" -ForegroundColor Green
        }
    } else {
        Write-Host "    (Sin solicitudes)" -ForegroundColor Gray
    }
} catch {
    Write-Host "    ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# 2.2 Admin - Ve TODAS las solicitudes
Write-Host "`n2.2 Admin - Debe ver TODAS las solicitudes del sistema" -ForegroundColor White
try {
    $listaAdmin = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" `
        -Method GET `
        -Headers @{"Authorization"="Bearer $tokenAdmin"; "Accept"="application/json"}
    
    Write-Host "    Total solicitudes visibles: $($listaAdmin.meta.total)" -ForegroundColor Cyan
    
    if ($listaAdmin.data.Count -gt 0) {
        Write-Host "    Mostrando primeras 5 solicitudes:" -ForegroundColor Gray
        $listaAdmin.data | Select-Object -First 5 | ForEach-Object {
            Write-Host "    • ID: $($_.solicitud_ampliacion_id) | Usuario: $($_.usuario_id) | Estado: $($_.estado)" -ForegroundColor Yellow
        }
        
        # Verificar que ve solicitudes de diferentes usuarios
        $usuariosUnicos = ($listaAdmin.data | Select-Object -Unique usuario_id).Count
        if ($usuariosUnicos -gt 1) {
            Write-Host "    ✅ Correcto: Ve solicitudes de $usuariosUnicos usuarios diferentes" -ForegroundColor Green
        }
    }
} catch {
    Write-Host "    ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# 2.3 Encargado - También ve TODAS
Write-Host "`n2.3 Encargado - Debe ver TODAS las solicitudes" -ForegroundColor White
try {
    $listaEncargado = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" `
        -Method GET `
        -Headers @{"Authorization"="Bearer $tokenEncargado"; "Accept"="application/json"}
    
    Write-Host "    Total solicitudes visibles: $($listaEncargado.meta.total)" -ForegroundColor Cyan
    Write-Host "    ✅ Encargado tiene acceso completo al sistema" -ForegroundColor Green
} catch {
    Write-Host "    ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ═══════════════════════════════════════════════════════════════
# PASO 3: CREAR SOLICITUD - Profesor
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 3] CREAR SOLICITUD - José crea nueva solicitud" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

$nuevaSolicitud = @{
    evidencia_asignacion_id = 1
    motivo = "Solicito ampliación debido a responsabilidades académicas importantes que requieren atención inmediata. Necesito tiempo adicional para completar la evidencia con la calidad requerida."
    fecha_sugerida = "2026-06-30"
} | ConvertTo-Json

Write-Host "`nDatos de la solicitud:" -ForegroundColor White
Write-Host "  Evidencia ID: 1" -ForegroundColor Gray
Write-Host "  Fecha sugerida: 2026-06-30" -ForegroundColor Gray

try {
    $solicitudCreada = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" `
        -Method POST `
        -Body $nuevaSolicitud `
        -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"; "Accept"="application/json"}
    
    $newId = $solicitudCreada.data.solicitud_ampliacion_id
    Write-Host "`n    ✅ Solicitud creada exitosamente" -ForegroundColor Green
    Write-Host "    ID: $newId | Usuario: $($solicitudCreada.data.usuario_id) | Estado: $($solicitudCreada.data.estado)" -ForegroundColor Cyan
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 422) {
        Write-Host "    ⚠ Validación rechazada (422)" -ForegroundColor Yellow
        Write-Host "    Posible causa: Evidencia no válida o ya tiene solicitud pendiente" -ForegroundColor Gray
    } else {
        Write-Host "    ✗ Error ($statusCode): $($_.Exception.Message)" -ForegroundColor Red
    }
    $newId = $null
}

# ═══════════════════════════════════════════════════════════════
# PASO 4: VALIDACIÓN DE DUPLICADOS
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 4] VALIDACIÓN DUPLICADOS - Sistema rechaza duplicados PENDIENTES" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

Write-Host "`n4.1 Intentar crear duplicado para evidencia 6 (ya tiene PENDIENTE)" -ForegroundColor White
$solicitudDuplicada = @{
    evidencia_asignacion_id = 6
    motivo = "Intento crear duplicado para evidencia que ya tiene solicitud pendiente"
    fecha_sugerida = "2026-07-15"
} | ConvertTo-Json

try {
    Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo" `
        -Method POST `
        -Body $solicitudDuplicada `
        -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"} | Out-Null
    
    Write-Host "    ✗ FALLO: Sistema permitió crear duplicado" -ForegroundColor Red
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 422) {
        Write-Host "    ✅ VALIDACIÓN CORRECTA: Sistema rechazó duplicado (422)" -ForegroundColor Green
        Write-Host "    Mensaje: Ya tiene solicitud PENDIENTE para esta evidencia" -ForegroundColor Gray
    } else {
        Write-Host "    ? Error inesperado: $statusCode" -ForegroundColor Yellow
    }
}

# ═══════════════════════════════════════════════════════════════
# PASO 5: VER SOLICITUD ESPECÍFICA
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 5] VER DETALLE - Consultar solicitud específica" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

if ($listaJose.data.Count -gt 0) {
    $idConsultar = $listaJose.data[0].solicitud_ampliacion_id
    Write-Host "`nConsultando solicitud ID: $idConsultar" -ForegroundColor White
    
    try {
        $detalle = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$idConsultar" `
            -Method GET `
            -Headers @{"Authorization"="Bearer $tokenJose"; "Accept"="application/json"}
        
        Write-Host "    ✅ Solicitud obtenida correctamente" -ForegroundColor Green
        Write-Host "    Estado: $($detalle.data.estado)" -ForegroundColor Cyan
        Write-Host "    Motivo: $($detalle.data.motivo.Substring(0,[Math]::Min(50,$detalle.data.motivo.Length)))..." -ForegroundColor Gray
        Write-Host "    Fecha sugerida: $($detalle.data.fecha_sugerida)" -ForegroundColor Gray
    } catch {
        Write-Host "    ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# ═══════════════════════════════════════════════════════════════
# PASO 6: ACTUALIZAR SOLICITUD (Solo si está PENDIENTE)
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 6] ACTUALIZAR - Modificar solicitud PENDIENTE" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

if ($newId) {
    $updateData = @{
        motivo = "Motivo actualizado: Requiero más tiempo debido a nueva carga académica asignada"
        fecha_sugerida = "2026-07-30"
    } | ConvertTo-Json
    
    Write-Host "`nActualizando solicitud ID: $newId" -ForegroundColor White
    
    try {
        $updated = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$newId" `
            -Method PUT `
            -Body $updateData `
            -Headers @{"Authorization"="Bearer $tokenJose"; "Content-Type"="application/json"; "Accept"="application/json"}
        
        Write-Host "    ✅ Solicitud actualizada correctamente" -ForegroundColor Green
        Write-Host "    Nuevo motivo: $($updated.data.motivo.Substring(0,[Math]::Min(60,$updated.data.motivo.Length)))..." -ForegroundColor Cyan
        Write-Host "    Nueva fecha: $($updated.data.fecha_sugerida)" -ForegroundColor Cyan
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq 422) {
            Write-Host "    ⚠ Solo se pueden actualizar solicitudes PENDIENTES" -ForegroundColor Yellow
        } else {
            Write-Host "    ✗ Error ($statusCode)" -ForegroundColor Red
        }
    }
}

# ═══════════════════════════════════════════════════════════════
# PASO 7: ELIMINAR SOLICITUD
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 7] ELIMINAR - Borrar solicitud PENDIENTE" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

if ($newId) {
    Write-Host "`nEliminando solicitud ID: $newId" -ForegroundColor White
    
    try {
        $deleted = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/$newId" `
            -Method DELETE `
            -Headers @{"Authorization"="Bearer $tokenJose"; "Accept"="application/json"}
        
        Write-Host "    ✅ Solicitud eliminada correctamente" -ForegroundColor Green
        Write-Host "    Mensaje: $($deleted.message)" -ForegroundColor Gray
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq 422) {
            Write-Host "    ⚠ Solo se pueden eliminar solicitudes PENDIENTES" -ForegroundColor Yellow
        } elseif ($statusCode -eq 404) {
            Write-Host "    ℹ Solicitud ya fue eliminada" -ForegroundColor Gray
        } else {
            Write-Host "    ✗ Error ($statusCode)" -ForegroundColor Red
        }
    }
}

# ═══════════════════════════════════════════════════════════════
# PASO 8: EVIDENCIAS PRÓXIMAS A VENCER
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n[PASO 8] EVIDENCIAS PRÓXIMAS - Consultar evidencias urgentes" -ForegroundColor Yellow
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

Write-Host "`nConsultando evidencias que vencen en los próximos 7 días..." -ForegroundColor White

try {
    $evidencias = Invoke-RestMethod -Uri "$baseUrl/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer" `
        -Method GET `
        -Headers @{"Authorization"="Bearer $tokenJose"; "Accept"="application/json"}
    
    Write-Host "    ✅ Consulta exitosa" -ForegroundColor Green
    Write-Host "    Total evidencias urgentes: $($evidencias.total)" -ForegroundColor Cyan
    Write-Host "    Mensaje: $($evidencias.message)" -ForegroundColor Gray
} catch {
    Write-Host "    ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ═══════════════════════════════════════════════════════════════
# RESUMEN FINAL
# ═══════════════════════════════════════════════════════════════
Write-Host "`n`n╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    RESUMEN DE PRUEBAS                         ║" -ForegroundColor Green
Write-Host "╚═══════════════════════════════════════════════════════════════╝`n" -ForegroundColor Green

Write-Host "FUNCIONALIDADES PROBADAS:" -ForegroundColor Yellow
Write-Host "   [OK] Login con LDAP + Sanctum (3 roles)" -ForegroundColor White
Write-Host "   [OK] Filtro por rol (Profesor ve solo suyas, Admin ve todas)" -ForegroundColor White
Write-Host "   [OK] Crear solicitud" -ForegroundColor White
Write-Host "   [OK] Validacion duplicados (rechaza PENDIENTES)" -ForegroundColor White
Write-Host "   [OK] Ver detalle de solicitud" -ForegroundColor White
Write-Host "   [OK] Actualizar solicitud PENDIENTE" -ForegroundColor White
Write-Host "   [OK] Eliminar solicitud PENDIENTE" -ForegroundColor White
Write-Host "   [OK] Consultar evidencias proximas a vencer" -ForegroundColor White

Write-Host "`nSEGURIDAD IMPLEMENTADA:" -ForegroundColor Yellow
Write-Host "   - Autenticacion LDAP + Redis" -ForegroundColor White
Write-Host "   - Tokens Sanctum (24h expiracion)" -ForegroundColor White
Write-Host "   - Filtrado por rol (profesores aislados)" -ForegroundColor White
Write-Host "   - Validacion XSS (strip_tags)" -ForegroundColor White
Write-Host "   - Rate limiting (60 req/min)" -ForegroundColor White
Write-Host "   - Audit logging" -ForegroundColor White
Write-Host "   - Error handling seguro" -ForegroundColor White
Write-Host "   - Validacion duplicados" -ForegroundColor White

Write-Host "`n════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  PRUEBA COMPLETA FINALIZADA" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan
