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
        $this->info('🔍 Verificando plazos próximos a vencer...');

        // Obtener días de anticipación desde opciones
        $daysToCheck = array_map('trim', explode(',', $this->option('days')));
        $daysToCheck = array_map('intval', $daysToCheck);

        $this->info('📅 Días de anticipación: ' . implode(', ', $daysToCheck));

        $totalNotifications = 0;

        foreach ($daysToCheck as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            
            // Buscar asignaciones que vencen exactamente en N días
            $assignments = EvidenceAssignment::whereDate('fecha_limite', $targetDate)
                ->where('estado_cumplimiento', '!=', 'completo') // Excluir completadas
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

        if ($totalNotifications > 0) {
            $this->info("✅ {$totalNotifications} notificaciones de vencimiento creadas");
            
            Log::info('Comando CheckDeadlines ejecutado', [
                'notificaciones_creadas' => $totalNotifications,
                'dias_revisados' => $daysToCheck,
            ]);
        } else {
            $this->info('✅ No hay plazos próximos a vencer');
        }

        return Command::SUCCESS;
    }
}

