<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends BaseCareer
{
    /** @use HasFactory<\Database\Factories\ProcessFactory> */
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'PROCESO';

    // Clave primaria
    protected $primaryKey = 'proceso_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'ciclo_acreditacion_id',
        'nombre',
        'activo'
    ];

    /**
     * Relación: Un proceso pertenece a un ciclo de acreditación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accreditationCycle()
    {
        return $this->belongsTo(AccreditationCycle::class, 'ciclo_acreditacion_id', 'ciclo_acreditacion_id');
    }
    

     //public function careerCampus()
    //{
       // return $this->hasOneThrough(
          //  CareerCampus::class,          // Modelo destino
           // AccreditationCycle::class,    // Modelo intermedio
           // 'ciclo_acreditacion_id',      // FK en AccreditationCycle
           // 'carrera_sede_id',            // FK en CareerCampus
           //// 'ciclo_acreditacion_id',      // PK local en PROCESO
           // 'carrera_sede_id'             // PK en AccreditationCycle
       // );
   // }

    

   
    /**
     * Relación: Un proceso tiene una autoevaluación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function autoevaluation()
    {
        return $this->hasOne(Autoevaluation::class, 'proceso_id', 'proceso_id');
    }

    /**
     * Relación: Un proceso tiene un compromiso de mejora.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function improvementCommitment()
    {
        return $this->hasOne(ImprovementCommitment::class, 'proceso_id', 'proceso_id');
    }
}
