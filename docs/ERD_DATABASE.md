# Diagrama Entidad-Relación — Base de Datos SAAC

> Generado a partir de las migraciones Laravel del proyecto.
> Para visualizarlo en VS Code instala la extensión **"Markdown Preview Mermaid Support"** (`bierner.markdown-mermaid`) y abre el preview (`Ctrl+Shift+V`).
>
> **Última actualización:** migración `040` — se añadieron tablas para el **modelo flexible** (`ELEMENTO`, `ELEMENTO_ASIGNACION`, `APROBACION_ELEMENTO`, `COMPROMISO_MEJORA_ELEMENTO`, `COMPROMISO_MEJORA_ELEMENTO_ASIGNACION`, `SOLICITUD_AMPLIACION_ELEMENTO`, `INFORME_ARCHIVO`); se actualizaron `MODELO_ESTRUCTURA`, `PROCESO`, `ARCHIVO` y `SOLICITUD_AMPLIACION`.

```mermaid
erDiagram

    UNIVERSIDAD {
        bigint universidad_id PK
        string nombre
        boolean activo
    }
    SEDE {
        bigint sede_id PK
        bigint universidad_id FK
        string nombre
        boolean activo
    }
    CARRERA {
        bigint carrera_id PK
        string nombre
        boolean activo
    }
    CARRERA_SEDE {
        bigint carrera_sede_id PK
        bigint carrera_id FK
        bigint sede_id FK
    }
    CICLO_ACREDITACION {
        bigint ciclo_acreditacion_id PK
        bigint carrera_sede_id FK
        string nombre
    }
    PROCESO {
        bigint proceso_id PK
        bigint ciclo_acreditacion_id FK
        enum tipo_proceso
        date fecha_inicio
        date fecha_finalizacion
        boolean activo
    }
    MODELO_ESTRUCTURA {
        bigint modelo_estructura_id PK
        string nombre
        text descripcion
        enum tipo
        string version
        boolean activo
        json tipos_requieren_archivo
        json tipos_asignables
        json tipos_jerarquia
    }
    ELEMENTO {
        bigint elemento_id PK
        bigint padre_id FK
        bigint modelo_estructura_id FK
        string tipo
        string nombre
        enum categoria
        string nomenclatura
        text descripcion
        boolean activo
        enum estado
        date fecha_limite
    }
    AUTOEVALUACION {
        bigint autoevaluacion_id PK
        bigint proceso_id FK
        date fecha_inicio
        date fecha_fin
    }
    COMPROMISO_MEJORA {
        bigint compromiso_mejora_id PK
        bigint proceso_id FK
        text descripcion
        date fecha_inicio
        date fecha_fin
        enum estado
        boolean activo
    }
    COMPROMISO_MEJORA_ELEMENTO {
        bigint compromiso_elemento_id PK
        bigint proceso_id FK
        text descripcion
        date fecha_inicio
        date fecha_fin
        enum estado
        boolean activo
    }
    USUARIO {
        bigint usuario_id PK
        string cedula
        string nombre
        string email
        string status
    }
    CARRERA_USUARIO {
        bigint carrera_usuario_id PK
        bigint usuario_id FK
        bigint carrera_id FK
    }
    TIPO_ACCION {
        bigint tipo_accion_id PK
        string descripcion
    }
    BITACORA {
        bigint bitacora_id PK
        bigint tipo_accion_id FK
        bigint usuario_id FK
        string modulo
        timestamp fecha_hora
        text detalle
    }
    COMENTARIO {
        bigint comentario_id PK
        bigint usuario_id FK
        string commentable_type
        bigint commentable_id
        text texto
    }
    DIMENSION {
        bigint dimension_id PK
        string nombre
        string nomenclatura
        boolean activo
    }
    COMPONENTE {
        bigint componente_id PK
        bigint dimension_id FK
        string nombre
        string nomenclatura
        boolean activo
    }
    CRITERIO {
        bigint criterio_id PK
        bigint componente_id FK
        string descripcion
        string nomenclatura
        boolean activo
    }
    ESTANDAR {
        bigint estandar_id PK
        bigint criterio_id FK
        string descripcion
        boolean activo
    }
    ESTADO_EVIDENCIA {
        bigint estado_evidencia_id PK
        string nombre
    }
    EVIDENCIA {
        bigint evidencia_id PK
        bigint criterio_id FK
        bigint estado_evidencia_id FK
        string descripcion
        string nomenclatura
        boolean activo
    }
    ARCHIVO {
        bigint archivo_id PK
        bigint evidencia_id FK
        bigint elemento_id FK
        bigint usuario_id FK
        bigint proceso_id FK
        timestamp fecha_subida
        enum tipo
        string path
        text url
        string nombre_original
        bigint tamanio
        string tipo_mime
        boolean is_publico
        string token_publico
        timestamp link_expira_en
    }
    INFORME_ARCHIVO {
        bigint informe_archivo_id PK
        bigint proceso_id FK
        bigint usuario_id FK
        bigint usuario_publicacion_id FK
        timestamp fecha_subida
        string tipo
        string path
        text url
        string nombre_original
        bigint tamanio
        string tipo_mime
        boolean is_publico
        string token_publico
        timestamp link_expira_en
        enum estado
        timestamp fecha_publicacion
        text observaciones
    }
    EVIDENCIA_ASIGNACION {
        bigint evidencia_asignacion_id PK
        bigint proceso_id FK
        bigint evidencia_id FK
        bigint usuario_id FK
        string estado
        datetime fecha_asignacion
        datetime fecha_limite
    }
    ELEMENTO_ASIGNACION {
        bigint elemento_asignacion_id PK
        bigint elemento_id FK
        bigint usuario_id FK
        bigint proceso_id FK
        bigint asignado_por FK
        enum estado
        date fecha_limite
        text comentario
    }
    SOLICITUD_AMPLIACION {
        bigint solicitud_ampliacion_id PK
        bigint evidencia_asignacion_id FK
        bigint elemento_asignacion_id FK
        bigint usuario_id FK
        bigint usuario_resolutor_id FK
        string motivo
        datetime fecha_sugerida
        enum estado
        datetime fecha_resolucion
        string justificacion
    }
    SOLICITUD_AMPLIACION_ELEMENTO {
        bigint solicitud_ampliacion_elemento_id PK
        bigint elemento_asignacion_id FK
        bigint usuario_id FK
        bigint usuario_resolutor_id FK
        string motivo
        datetime fecha_sugerida
        enum estado
        datetime fecha_resolucion
        string justificacion
    }
    APROBACION_CRITERIO {
        bigint aprobacion_criterio_id PK
        bigint criterio_id FK
        bigint proceso_id FK
        bigint usuario_id FK
        enum estado
        string comentario
    }
    APROBACION_EVIDENCIA {
        bigint aprobacion_evidencia_id PK
        bigint evidencia_id FK
        bigint proceso_id FK
        bigint criterio_aprobacion_id FK
        bigint usuario_id FK
        enum estado
    }
    APROBACION_ELEMENTO {
        bigint aprobacion_elemento_id PK
        bigint elemento_id FK
        bigint proceso_id FK
        bigint usuario_id FK
        enum estado
        string comentario
        date nueva_fecha_limite
    }
    COMPROMISO_MEJORA_EVIDENCIA {
        bigint compromiso_mejora_id FK
        bigint evidencia_id FK
    }
    COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION {
        bigint compromiso_mejora_id FK
        bigint evidencia_asignacion_id FK
        text comentario
    }
    COMPROMISO_MEJORA_ELEMENTO_ASIGNACION {
        bigint compromiso_elemento_id FK
        bigint elemento_asignacion_id FK
        string comentario
    }

    %% ── Jerarquía institucional ──
    UNIVERSIDAD ||--o{ SEDE : "tiene"
    SEDE ||--o{ CARRERA_SEDE : "pertenece a"
    CARRERA ||--o{ CARRERA_SEDE : "ofertada en"
    CARRERA_SEDE ||--o{ CICLO_ACREDITACION : "tiene"
    CICLO_ACREDITACION ||--o{ PROCESO : "contiene"

    %% ── Modelo de estructura flexible ──
    MODELO_ESTRUCTURA ||--o{ ELEMENTO : "define"
    ELEMENTO ||--o{ ELEMENTO : "contiene"

    %% ── Proceso → subentidades ──
    PROCESO ||--o| AUTOEVALUACION : "genera"
    PROCESO ||--o{ COMPROMISO_MEJORA : "genera"
    PROCESO ||--o{ COMPROMISO_MEJORA_ELEMENTO : "genera"
    PROCESO ||--o{ EVIDENCIA_ASIGNACION : "tiene"
    PROCESO ||--o{ ELEMENTO_ASIGNACION : "tiene"
    PROCESO ||--o{ ARCHIVO : "almacena"
    PROCESO ||--o{ INFORME_ARCHIVO : "almacena"
    PROCESO ||--o{ APROBACION_CRITERIO : "registra"
    PROCESO ||--o{ APROBACION_EVIDENCIA : "registra"
    PROCESO ||--o{ APROBACION_ELEMENTO : "registra"

    %% ── Catálogo tradicional (SINAES 2018) ──
    DIMENSION ||--o{ COMPONENTE : "agrupa"
    COMPONENTE ||--o{ CRITERIO : "tiene"
    CRITERIO ||--o{ ESTANDAR : "define"
    CRITERIO ||--o{ EVIDENCIA : "requiere"
    CRITERIO ||--o{ APROBACION_CRITERIO : "es aprobado en"

    %% ── Evidencias (modelo tradicional) ──
    ESTADO_EVIDENCIA ||--o{ EVIDENCIA : "clasifica"
    EVIDENCIA ||--o{ ARCHIVO : "documentada con"
    EVIDENCIA ||--o{ EVIDENCIA_ASIGNACION : "asignada en"
    EVIDENCIA ||--o{ COMPROMISO_MEJORA_EVIDENCIA : "vinculada a"
    EVIDENCIA ||--o{ APROBACION_EVIDENCIA : "aprobada en"

    %% ── Elementos (modelo flexible) ──
    ELEMENTO ||--o{ ARCHIVO : "documentado con"
    ELEMENTO ||--o{ ELEMENTO_ASIGNACION : "asignado en"
    ELEMENTO ||--o{ APROBACION_ELEMENTO : "aprobado en"

    %% ── Usuarios ──
    USUARIO ||--o{ BITACORA : "genera"
    USUARIO ||--o{ COMENTARIO : "escribe"
    USUARIO ||--o{ ARCHIVO : "sube"
    USUARIO ||--o{ INFORME_ARCHIVO : "sube"
    USUARIO ||--o{ CARRERA_USUARIO : "asignado a"
    USUARIO ||--o{ EVIDENCIA_ASIGNACION : "responsable de"
    USUARIO ||--o{ ELEMENTO_ASIGNACION : "responsable de"
    USUARIO ||--o{ SOLICITUD_AMPLIACION : "solicita"
    USUARIO ||--o{ SOLICITUD_AMPLIACION_ELEMENTO : "solicita"
    USUARIO ||--o{ APROBACION_CRITERIO : "aprueba"
    USUARIO ||--o{ APROBACION_EVIDENCIA : "aprueba"
    USUARIO ||--o{ APROBACION_ELEMENTO : "aprueba"
    CARRERA ||--o{ CARRERA_USUARIO : "tiene"

    %% ── Auditoría ──
    TIPO_ACCION ||--o{ BITACORA : "categoriza"

    %% ── Compromisos de mejora (modelo tradicional) ──
    COMPROMISO_MEJORA ||--o{ COMPROMISO_MEJORA_EVIDENCIA : "incluye"
    COMPROMISO_MEJORA ||--o{ COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION : "vincula"
    EVIDENCIA_ASIGNACION ||--o{ COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION : "vinculada a"
    EVIDENCIA_ASIGNACION ||--o{ SOLICITUD_AMPLIACION : "tiene"

    %% ── Compromisos de mejora (modelo flexible) ──
    COMPROMISO_MEJORA_ELEMENTO ||--o{ COMPROMISO_MEJORA_ELEMENTO_ASIGNACION : "vincula"
    ELEMENTO_ASIGNACION ||--o{ COMPROMISO_MEJORA_ELEMENTO_ASIGNACION : "vinculada a"
    ELEMENTO_ASIGNACION ||--o{ SOLICITUD_AMPLIACION_ELEMENTO : "tiene"

    %% ── Aprobaciones (modelo tradicional) ──
    APROBACION_CRITERIO ||--o{ APROBACION_EVIDENCIA : "contiene"
```

---

## Tablas de sistema (no incluidas en el diagrama)

| Tabla | Descripción |
|-------|-------------|
| `cache` | Caché de Laravel |
| `jobs` / `job_batches` / `failed_jobs` | Colas de trabajo |
| `sessions` | Sesiones de usuario |
| `personal_access_tokens` | Tokens Sanctum |
| `permissions` / `roles` / `model_has_roles` / `model_has_permissions` / `role_has_permissions` | Paquete Spatie Permissions |
| `notifications` | Notificaciones Laravel |
