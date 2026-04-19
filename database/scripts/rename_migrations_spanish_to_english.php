<?php
/**
 * Script: rename_migrations_spanish_to_english.php
 * Purpose: Update migration names in the `migrations` table after pulling the
 *          "rename migration files from Spanish to English" commit.
 *
 * WHEN TO RUN: Only if your local `migrations` table still has the old Spanish
 *              names (i.e. you had already run migrations before pulling this commit).
 *
 * HOW TO RUN:
 *   php database/scripts/rename_migrations_spanish_to_english.php
 *
 * Configure the DB connection below if your local settings differ.
 */

$host = '127.0.0.1';
$port = '3307';
$db   = 'saac_una';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$renames = [
    '007a_create_modelo_estructura_table'                              => '007a_create_structure_model_table',
    '027_make_usuario_id_nullable_in_bitacora_table'                   => '027_make_user_id_nullable_in_audit_log_table',
    '031_create_solicitud_ampliacion_table'                            => '031_create_extension_request_table',
    '034_add_password_column_to_usuario_table'                         => '034_add_password_column_to_user_table',
    '039_create_elemento_table'                                        => '039_create_element_table',
    '040_add_estado_to_criterio_table'                                 => '040_add_status_to_criterion_table',
    '041_fix_evidencia_estado_enum_to_pascal_case'                     => '041_fix_evidence_status_enum_to_pascal_case',
    '042_add_vencido_to_criterio_estado'                               => '042_add_expired_to_criterion_status',
    '043_add_elemento_id_to_evidencia_table'                           => '043_add_element_id_to_evidence_table',
    '044_add_estado_fecha_limite_to_elemento_table'                    => '044_add_status_deadline_to_element_table',
    '045_create_elemento_asignacion_table'                             => '045_create_element_assignment_table',
    '046_add_elemento_id_to_archivo_table'                             => '046_add_element_id_to_file_table',
    '047_remove_elemento_id_from_evidencia_table'                      => '047_remove_element_id_from_evidence_table',
    '048_flexible_retroalimentacion_ampliacion'                        => '048_flexible_feedback_extension',
    '049_add_tipos_asignables_to_modelo_estructura_table'              => '049_add_assignable_types_to_structure_model_table',
    '049_create_elemento_approvals_table'                              => '049_create_element_approvals_table',
    '052_add_cancelada_to_solicitud_ampliacion_estado'                 => '052_add_cancelled_to_extension_request_status',
    '053_create_solicitud_ampliacion_elemento_table'                   => '053_create_element_extension_request_table',
    '054_add_pendiente_to_criterion_approval_estado'                   => '054_add_pending_to_criterion_approval_status',
    '055_add_incompleto_to_criterion_approval_estado'                  => '055_add_incomplete_to_criterion_approval_status',
    '056_add_comentario_to_evidence_approvals'                         => '056_add_comment_to_evidence_approvals',
    '057_add_nueva_fecha_limite_to_criterion_approvals'                => '057_add_new_deadline_to_criterion_approvals',
    '058_add_nueva_fecha_limite_to_evidence_approvals'                 => '058_add_new_deadline_to_evidence_approvals',
    '061_add_nueva_fecha_limite_to_element_approvals'                  => '061_add_new_deadline_to_element_approvals',
    '2026_03_18_203307_add_status_to_ciclo_acreditacion_table'         => '2026_03_18_203307_add_status_to_accreditation_cycle_table',
    '2026_03_31_181718_add_asignacion_elemento_to_notificacion_tipo_evento' => '2026_03_31_181718_add_element_assignment_to_notification_event_type',
    '2026_03_31_add_fulltext_index_to_elemento_table'                  => '2026_03_31_add_fulltext_index_to_element_table',
    '2026_04_04_164757_add_elemento_asignacion_id_to_solicitud_ampliacion_table' => '2026_04_04_164757_add_element_assignment_id_to_extension_request_table',
    '2026_04_05_142059_add_elemento_approval_types_to_notificacion_tipo_evento'  => '2026_04_05_142059_add_element_approval_types_to_notification_event_type',
    '2026_04_05_add_tipos_jerarquia_to_modelo_estructura_table'        => '2026_04_05_add_hierarchy_types_to_structure_model_table',
];

$stmt    = $pdo->prepare('UPDATE migrations SET migration = :new WHERE migration = :old');
$updated = 0;
$skipped = 0;

foreach ($renames as $old => $new) {
    $stmt->execute([':new' => $new, ':old' => $old]);
    if ($stmt->rowCount() > 0) {
        echo "OK   $old\n";
        $updated++;
    } else {
        echo "SKIP $old (already updated or not found)\n";
        $skipped++;
    }
}

echo "\nDone. Updated: $updated | Skipped: $skipped\n";
