# ANÁLISIS: Módulo Process - Problemas de nomenclatura

## ❌ PROBLEMAS ENCONTRADOS:

### 1. **ProcessController.php** (líneas 130-131, 137, 142)
Variables en español:
```php
$procesoId   = $process->proceso_id;      // ❌ Español
$tipoProceso = $process->tipo_proceso;    // ❌ Español
```

**Debe ser:**
```php
$processId  = $process->proceso_id;       // ✅ Inglés
$processType = $process->tipo_proceso;    // ✅ Inglés
```

---

### 2. **ProcessRequest.php** (líneas 75, 78, 86, 90, 91)
Variables en español:
```php
$cicloId = $this->input('ciclo_acreditacion_id');    // ❌ Español
$tipoProceso = $this->input('tipo_proceso');         // ❌ Español
```

**Debe ser:**
```php
$cycleId = $this->input('ciclo_acreditacion_id');    // ✅ Inglés
$processType = $this->input('tipo_proceso');         // ✅ Inglés
```

---

### 3. **ProcessService.php** (línea 24)
Llamada a método renombrado:
```php
'accreditationCycle.modeloEstructura',    // ❌ Método viejo
```

**Debe ser:**
```php
'accreditationCycle.structureModel',      // ✅ Método nuevo
```

---

## ✅ COSAS QUE ESTÁN BIEN:

- Nombres de columnas BD (proceso_id, tipo_proceso, ciclo_acreditacion_id) ✅
- Nombres de métodos en Process.php (accreditationCycle, autoevaluation, improvementCommitment) ✅
- Nombres de métodos en ProcessService (getAll, findById, create, update, toggleActive) ✅
- Mensajes de usuario en español ✅

---

## 📝 TOTAL A CORREGIR:
- 3 variables en ProcessController
- 2 variables en ProcessRequest  
- 1 llamada a método en ProcessService
