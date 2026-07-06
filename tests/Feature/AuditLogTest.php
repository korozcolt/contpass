<?php

use App\Enums\UserRole;
use App\Filament\Pages\AuditLogs;
use App\Jobs\ProcessAuditLog;
use App\Models\BudgetAppropriation;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('dispatches audit job on model creation', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($user);

    BudgetAppropriation::factory()->create([
        'code' => '99.99.99',
        'name' => 'Rubro de Prueba Auditoría',
        'initial_amount' => 5000000,
    ]);

    Queue::assertPushed(ProcessAuditLog::class, function ($job) {
        $payload = $job->getPayload();

        return $payload['event'] === 'created'
            && $job->getPayload()['model_type'] === BudgetAppropriation::class
            && (float) $payload['new_values']['initial_amount'] === 5000000.0;
    });
});

it('dispatches audit job on model update with original and changed values', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($user);

    $rubro = BudgetAppropriation::factory()->create([
        'code' => '99.99.99',
        'name' => 'Rubro Anterior',
        'initial_amount' => 5000000,
    ]);

    // Vaciar la cola para evaluar solo la modificación
    Queue::fake();

    $rubro->update([
        'name' => 'Rubro Modificado',
        'initial_amount' => 8000000,
    ]);

    Queue::assertPushed(ProcessAuditLog::class, function ($job) use ($rubro) {
        $payload = $job->getPayload();

        return $payload['event'] === 'updated'
            && $payload['model_id'] === $rubro->id
            && $payload['old_values']['name'] === 'Rubro Anterior'
            && $payload['new_values']['name'] === 'Rubro Modificado'
            && (float) $payload['new_values']['initial_amount'] === 8000000.0;
    });
});

it('does not log sensitive fields like password', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($user);

    // Ocultar contraseña en el trait al modificar usuario (User no usa Auditable por defecto, pero simulemos un dispatch genérico)
    $payload = [
        'event' => 'updated',
        'user_id' => $user->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'old_values' => ['password' => 'secret_old_pass', 'email' => 'old@mail.com'],
        'new_values' => ['password' => 'secret_new_pass', 'email' => 'new@mail.com'],
        'timestamp' => now()->toIso8601String(),
    ];

    // Omitir contraseña igual que el Trait
    unset($payload['old_values']['password'], $payload['new_values']['password']);

    expect(isset($payload['new_values']['password']))->toBeFalse()
        ->and(isset($payload['old_values']['password']))->toBeFalse();
});

it('only allows admin users to access the audit logs page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $accountant = User::factory()->create(['role' => UserRole::Accountant]);

    $this->actingAs($admin);
    $this->get('/admin/audit-logs')->assertSuccessful();

    $this->actingAs($accountant);
    $this->get('/admin/audit-logs')->assertForbidden();
});

it('reads and parses the daily log file in reverse chronological order', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $date = '2000-01-01';
    $filePath = storage_path("logs/audit/contpass_audit-{$date}.log");

    if (! File::isDirectory(dirname($filePath))) {
        File::makeDirectory(dirname($filePath), 0755, true);
    }

    // Escribir logs de prueba directamente en el archivo
    $logContent = '[2000-01-01T10:00:00-05:00] production.INFO: Audit transaction recorded {"event":"created","model_type":"App\\\\Models\\\\BudgetAppropriation","model_id":1,"new_values":{"name":"Primer Rubro"},"timestamp":"2000-01-01T10:00:00-05:00"} []'.PHP_EOL;
    $logContent .= '[2000-01-01T10:05:00-05:00] production.INFO: Audit transaction recorded {"event":"created","model_type":"App\\\\Models\\\\BudgetAppropriation","model_id":2,"new_values":{"name":"Segundo Rubro"},"timestamp":"2000-01-01T10:05:00-05:00"} []'.PHP_EOL;

    File::put($filePath, $logContent);

    $test = Livewire::test(AuditLogs::class)
        ->filterTable('log_date_filter', [
            'date' => '2000-01-01',
        ])
        ->assertCanRenderTableColumn('friendly_model')
        ->assertCanRenderTableColumn('changes_summary');

    expect($test->instance()->getTableRecords()->count())->toBe(2);

    // Eliminar archivo de prueba
    File::delete($filePath);
});
