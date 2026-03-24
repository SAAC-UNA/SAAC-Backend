<?php

namespace App\Console\Commands;

use App\Models\EvidenceAssignment;
use App\Events\DeadlineApproaching;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando: Verificar Plazos Próximos a Vencer
 * 
 * HU-018: Notificaciones automáticas
 * 
 * Este comando se debe ejecutar diariamente para verificar evidencias
 * con plazos próximos a vencer y disparar notificaciones.
 * 
 * Configuración en app/Console/Kernel.php:
 * $schedule->command('notifications:check-deadlines')->daily();
 * 
 * Uso manual:
 * php artisan notifications:check-deadlines
 * php artisan notifications:check-deadlines --days=3,7,15
 */
class CheckDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-deadlines {--days=3,7,15 : Días de anticipación para notificar (separados por coma)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica plazos próximos a vencer y envía notificaciones a los responsables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando plazos próximos a vencer y vencidos...');

        // Obtener días de anticipación desde opciones
        $daysToCheck = array_map('trim', explode(',', $this->option('days')));
        $daysToCheck = array_map('intval', $daysToCheck);

        $this->info('📅 Días de anticipación: ' . implode(', ', $daysToCheck));

        $totalNotifications = 0;

        // 1. Verificar plazos próximos a vencer (anticipación)
        foreach ($daysToCheck as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            
            // Buscar asignaciones que vencen exactamente en N días
            $assignments = EvidenceAssignment::whereDate('fecha_limite', $targetDate)
                ->whereIn('estado', ['Pendiente', 'En Progreso']) // Excluir completadas
                ->with(['evidence', 'user'])
                ->get();

            $count = $assignments->count();

            if ($count > 0) {
                $this->line("  ⚠️  {$count} evidencias vencen en {$days} días:");

                foreach ($assignments as $assignment) {
                    // Disparar evento
                    event(new DeadlineApproaching($assignment, $days));
                    
                    $this->line("    - Evidencia {$assignment->evidence->nomenclatura} → Usuario {$assignment->user->nombre}");
                    $totalNotifications++;
                }
            } else {
                $this->line("  ✓ No hay evidencias que venzan en {$days} días");
            }
        }

        // 2. Verificar plazos VENCIDOS (fecha_limite < hoy)
        $expiredAssignments = EvidenceAssignment::where('fecha_limite', '<', now()->startOfDay())
            ->whereIn('estado', ['Pendiente', 'En Progreso'])
            ->with(['evidence', 'user'])
            ->get();

        $expiredCount = $expiredAssignments->count();

        if ($expiredCount > 0) {
            $this->error("  🚨 {$expiredCount} evidencias con plazo VENCIDO:");

            foreach ($expiredAssignments as $assignment) {
                $daysOverdue = now()->startOfDay()->diffInDays($assignment->fecha_limite, false);
                
                // Disparar evento con días negativos (plazo vencido)
                event(new DeadlineApproaching($assignment, (int)$daysOverdue));
                
                $this->line("    - Evidencia {$assignment->evidence->nomenclatura} → Usuario {$assignment->user->nombre} (vencido hace " . abs($daysOverdue) . " días)");
                $totalNotifications++;
            }
        } else {
            $this->line("  ✓ No hay plazos vencidos pendientes");
        }

        if ($totalNotifications > 0) {
            $this->info("✅ {$totalNotifications} notificaciones de vencimiento/expiración creadas");
            
            Log::info('Comando CheckDeadlines ejecutado', [
                'notificaciones_creadas' => $totalNotifications,
                'dias_revisados' => $daysToCheck,
                'plazos_vencidos' => $expiredCount,
            ]);
        } else {
            $this->info('✅ No hay plazos próximos a vencer ni vencidos');
        }

        return Command::SUCCESS;
    }
}

