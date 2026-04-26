<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = [
            'evidencia_id'  => null,
            'usuario_id'    => \App\Models\User::factory(),
            'proceso_id'    => \App\Models\Process::factory(),
            'fecha_subida'  => now(),
            'tipo'          => 'archivo',
            'path'          => $this->faker->filePath(),
            'url'           => null,
            'nombre_original' => $this->faker->word() . '.pdf',
            'is_publico'    => false,
            'token_publico' => null,
            'link_expira_en'=> null,
        ];

        if (Schema::hasColumn('ARCHIVO', 'elemento_id')) {
            $data['elemento_id'] = null;
        }

        return $data;
    }
}
