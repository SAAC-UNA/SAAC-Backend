<?php

use App\Models\File;
use App\Models\User;
use App\Models\Process;
use App\Models\StructureElement;
use App\Models\ElementAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

/**
 * Feature Tests — ElementFileController (HU-008 modelo flexible)
 *
 * Cubre los 7 endpoints bajo /api/elementos-archivos:
 *   GET    /                    → index
 *   POST   /                    → store (archivo y enlace)
 *   GET    /{archivo}           → show
 *   DELETE /{archivo}           → destroy
 *   GET    /{archivo}/download  → download
 *   POST   /{archivo}/make-public
 *   POST   /{archivo}/revoke-public
 */

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
    Storage::fake('simulated_nas');

    Log::shouldReceive('info', 'error', 'warning', 'debug')->andReturn(null);
    Event::fake();

    // Roles
    $rolDocente   = Role::firstOrCreate(['name' => 'Profesor',                    'guard_name' => 'api']);
    $rolEncargado = Role::firstOrCreate(['name' => 'Encargado de Acreditacion',   'guard_name' => 'api']);
    $rolSuper     = Role::firstOrCreate(['name' => 'Superusuario',                'guard_name' => 'api']);

    // Permisos necesarios para ElementFileController
    $permisos = ['archivos.view', 'archivos.upload', 'archivos.delete', 'archivos.download', 'archivos.make_public'];
    foreach ($permisos as $p) {
        $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'api']);
        $rolSuper->givePermissionTo($perm);
        $rolDocente->givePermissionTo($perm);
        $rolEncargado->givePermissionTo($perm);
    }

    // Usuarios
    $this->docente   = User::factory()->create(['status' => User::STATUS_ACTIVE]);
    $this->encargado = User::factory()->create(['status' => User::STATUS_ACTIVE]);
    $this->super     = User::factory()->create(['status' => User::STATUS_ACTIVE]);

    $this->docente->assignRole($rolDocente);
    $this->encargado->assignRole($rolEncargado);
    $this->super->assignRole($rolSuper);

    // Estructura flexible: ELEMENTO → ELEMENTO_ASIGNACION
    $proceso = Process::factory()->create();
    $this->elemento = StructureElement::factory()->create();
    $this->asignacion = ElementAssignment::factory()->create([
        'elemento_id' => $this->elemento->elemento_id,
        'usuario_id'  => $this->docente->usuario_id,
        'proceso_id'  => $proceso->proceso_id,
        'estado'      => ElementAssignment::ESTADO_PENDIENTE,
    ]);
    $this->proceso = $proceso;
});

/* ========== AUTENTICACIÓN ========== */

it('requires authentication for element file endpoints', function () {
    $this->getJson('/api/elementos-archivos')->assertStatus(401);
    $this->postJson('/api/elementos-archivos')->assertStatus(401);
});

/* ========== INDEX ========== */

it('can list element files', function () {
    File::factory()->count(3)->create([
        'elemento_id' => $this->elemento->elemento_id,
        'usuario_id'  => $this->docente->usuario_id,
        'proceso_id'  => $this->proceso->proceso_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson('/api/elementos-archivos?elemento_id=' . $this->elemento->elemento_id);

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'count'])
        ->assertJsonCount(3, 'data');
});

/* ========== STORE — ARCHIVO ========== */

it('can upload a file to an element', function () {
    $file = UploadedFile::fake()->create('informe.pdf', 512, 'application/pdf');

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elementos-archivos', [
            'tipo'        => 'archivo',
            'archivos'    => [$file],
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                '*' => ['archivo_id', 'nombre_original', 'tipo', 'fecha_subida'],
            ],
        ]);

    $this->assertDatabaseHas('ARCHIVO', [
        'elemento_id'     => $this->elemento->elemento_id,
        'usuario_id'      => $this->docente->usuario_id,
        'nombre_original' => 'informe.pdf',
        'tipo'            => 'archivo',
    ]);
});

/* ========== STORE — ENLACE ========== */

it('can save a link to an element', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elementos-archivos', [
            'tipo'             => 'enlace',
            'enlaces'          => ['https://drive.google.com/doc/xyz'],
            'enlaces_nombres'  => ['Plan Estratégico'],
            'elemento_id'      => $this->elemento->elemento_id,
            'proceso_id'       => $this->proceso->proceso_id,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('ARCHIVO', [
        'elemento_id'     => $this->elemento->elemento_id,
        'usuario_id'      => $this->docente->usuario_id,
        'nombre_original' => 'Plan Estratégico',
        'tipo'            => 'enlace',
        'url'             => 'https://drive.google.com/doc/xyz',
    ]);
});

/* ========== STORE — VALIDACIONES ========== */

it('requires elemento_id to upload', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elementos-archivos', [
            'tipo'       => 'archivo',
            'archivos'   => [$file],
            'proceso_id' => $this->proceso->proceso_id,
            // elemento_id ausente
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['elemento_id']);
});

it('requires proceso_id to upload', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elementos-archivos', [
            'tipo'        => 'archivo',
            'archivos'    => [$file],
            'elemento_id' => $this->elemento->elemento_id,
            // proceso_id ausente
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['proceso_id']);
});

it('rejects more than 5 files at once', function () {
    $files = array_map(
        fn($i) => UploadedFile::fake()->create("file{$i}.pdf", 100, 'application/pdf'),
        range(1, 6)
    );

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elementos-archivos', [
            'tipo'        => 'archivo',
            'archivos'    => $files,
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['archivos']);
});

/* ========== SHOW ========== */

it('can show a specific element file', function () {
    $archivo = File::factory()->create([
        'elemento_id' => $this->elemento->elemento_id,
        'usuario_id'  => $this->docente->usuario_id,
        'proceso_id'  => $this->proceso->proceso_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson("/api/elementos-archivos/{$archivo->archivo_id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['archivo_id' => $archivo->archivo_id]]);
});

it('returns 404 for nonexistent element file', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson('/api/elementos-archivos/99999');

    $response->assertStatus(404);
});

/* ========== DESTROY ========== */

it('owner can delete their element file', function () {
    $archivo = File::factory()->create([
        'elemento_id' => $this->elemento->elemento_id,
        'usuario_id'  => $this->docente->usuario_id,
        'proceso_id'  => $this->proceso->proceso_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->deleteJson("/api/elementos-archivos/{$archivo->archivo_id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('ARCHIVO', ['archivo_id' => $archivo->archivo_id]);
});

/* ========== MAKE PUBLIC / REVOKE PUBLIC ========== */

it('can make an element file public', function () {
    $archivo = File::factory()->create([
        'elemento_id'  => $this->elemento->elemento_id,
        'usuario_id'   => $this->docente->usuario_id,
        'proceso_id'   => $this->proceso->proceso_id,
        'token_publico' => null,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/elementos-archivos/{$archivo->archivo_id}/make-public");

    $response->assertStatus(200);

    $this->assertDatabaseHas('ARCHIVO', [
        'archivo_id' => $archivo->archivo_id,
    ]);
    $this->assertNotNull($archivo->fresh()->token_publico);
});

it('can revoke public access of an element file', function () {
    $archivo = File::factory()->create([
        'elemento_id'   => $this->elemento->elemento_id,
        'usuario_id'    => $this->docente->usuario_id,
        'proceso_id'    => $this->proceso->proceso_id,
        'token_publico' => 'existing-token-abc123',
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/elementos-archivos/{$archivo->archivo_id}/revoke-public");

    $response->assertStatus(200);

    $this->assertNull($archivo->fresh()->token_publico);
});
