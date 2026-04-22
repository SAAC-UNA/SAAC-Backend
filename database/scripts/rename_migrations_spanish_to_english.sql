-- ============================================================
-- Script: rename_migrations_spanish_to_english.sql
-- Purpose: Update migration names in the `migrations` table
--          after pulling branch Hu_028_Publicación-de-informe-de-acreditación-aprobado
--          (commit: refactor: rename migration files from Spanish to English naming convention)
--
-- WHEN TO RUN: Only if your local `migrations` table still has
--              the old Spanish names (i.e. you had run migrations
--              before pulling this rename commit).
--
-- HOW TO RUN (pick one):
--   Option A - MySQL CLI:
--     mysql -h 127.0.0.1 -P 3307 -u root -p saac_una < database/scripts/rename_migrations_spanish_to_english.sql
--
--   Option B - PHP (no MySQL in PATH):
--     php database/scripts/rename_migrations_spanish_to_english.php
--
--   Option C - TablePlus / DBeaver / phpMyAdmin:
--     Open the file and execute it against the saac_una database.
-- ============================================================

UPDATE migrations SET migration = '007a_create_structure_model_table'                                    WHERE migration = '007a_create_modelo_estructura_table';
UPDATE migrations SET migration = '027_make_user_id_nullable_in_audit_log_table'                         WHERE migration = '027_make_usuario_id_nullable_in_bitacora_table';
UPDATE migrations SET migration = '031_create_extension_request_table'                                   WHERE migration = '031_create_solicitud_ampliacion_table';
UPDATE migrations SET migration = '034_add_password_column_to_user_table'                                WHERE migration = '034_add_password_column_to_usuario_table';
UPDATE migrations SET migration = '039_create_element_table'                                             WHERE migration = '039_create_elemento_table';
UPDATE migrations SET migration = '040_add_status_to_criterion_table'                                    WHERE migration = '040_add_estado_to_criterio_table';
UPDATE migrations SET migration = '041_fix_evidence_status_enum_to_pascal_case'                          WHERE migration = '041_fix_evidencia_estado_enum_to_pascal_case';
UPDATE migrations SET migration = '042_add_expired_to_criterion_status'                                  WHERE migration = '042_add_vencido_to_criterio_estado';
UPDATE migrations SET migration = '043_add_element_id_to_evidence_table'                                 WHERE migration = '043_add_elemento_id_to_evidencia_table';
UPDATE migrations SET migration = '044_add_status_deadline_to_element_table'                             WHERE migration = '044_add_estado_fecha_limite_to_elemento_table';
UPDATE migrations SET migration = '045_create_element_assignment_table'                                  WHERE migration = '045_create_elemento_asignacion_table';
UPDATE migrations SET migration = '046_add_element_id_to_file_table'                                     WHERE migration = '046_add_elemento_id_to_archivo_table';
UPDATE migrations SET migration = '047_remove_element_id_from_evidence_table'                            WHERE migration = '047_remove_elemento_id_from_evidencia_table';
UPDATE migrations SET migration = '048_flexible_feedback_extension'                                      WHERE migration = '048_flexible_retroalimentacion_ampliacion';
UPDATE migrations SET migration = '049_add_assignable_types_to_structure_model_table'                    WHERE migration = '049_add_tipos_asignables_to_modelo_estructura_table';
UPDATE migrations SET migration = '049_create_element_approvals_table'                                   WHERE migration = '049_create_elemento_approvals_table';
UPDATE migrations SET migration = '052_add_cancelled_to_extension_request_status'                        WHERE migration = '052_add_cancelada_to_solicitud_ampliacion_estado';
UPDATE migrations SET migration = '053_create_element_extension_request_table'                           WHERE migration = '053_create_solicitud_ampliacion_elemento_table';
UPDATE migrations SET migration = '054_add_pending_to_criterion_approval_status'                         WHERE migration = '054_add_pendiente_to_criterion_approval_estado';
UPDATE migrations SET migration = '055_add_incomplete_to_criterion_approval_status'                      WHERE migration = '055_add_incompleto_to_criterion_approval_estado';
UPDATE migrations SET migration = '056_add_comment_to_evidence_approvals'                                WHERE migration = '056_add_comentario_to_evidence_approvals';
UPDATE migrations SET migration = '057_add_new_deadline_to_criterion_approvals'                          WHERE migration = '057_add_nueva_fecha_limite_to_criterion_approvals';
UPDATE migrations SET migration = '058_add_new_deadline_to_evidence_approvals'                           WHERE migration = '058_add_nueva_fecha_limite_to_evidence_approvals';
UPDATE migrations SET migration = '061_add_new_deadline_to_element_approvals'                            WHERE migration = '061_add_nueva_fecha_limite_to_element_approvals';
UPDATE migrations SET migration = '2026_03_18_203307_add_status_to_accreditation_cycle_table'            WHERE migration = '2026_03_18_203307_add_status_to_ciclo_acreditacion_table';
UPDATE migrations SET migration = '2026_03_31_181718_add_element_assignment_to_notification_event_type'  WHERE migration = '2026_03_31_181718_add_asignacion_elemento_to_notificacion_tipo_evento';
UPDATE migrations SET migration = '2026_03_31_add_fulltext_index_to_element_table'                       WHERE migration = '2026_03_31_add_fulltext_index_to_elemento_table';
UPDATE migrations SET migration = '2026_04_04_164757_add_element_assignment_id_to_extension_request_table' WHERE migration = '2026_04_04_164757_add_elemento_asignacion_id_to_solicitud_ampliacion_table';
UPDATE migrations SET migration = '2026_04_05_142059_add_element_approval_types_to_notification_event_type' WHERE migration = '2026_04_05_142059_add_elemento_approval_types_to_notificacion_tipo_evento';
UPDATE migrations SET migration = '2026_04_05_add_hierarchy_types_to_structure_model_table'              WHERE migration = '2026_04_05_add_tipos_jerarquia_to_modelo_estructura_table';
