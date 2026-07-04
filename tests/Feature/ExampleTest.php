<?php

use App\Models\User;

test('guests can view the public welcome page', function () {
    $this->get('/')->assertSuccessful()->assertSee('Mapa estructural del software');
});

test('legacy Blade entrypoints are deprecated', function () {
    foreach ([
        '/login',
        '/third-parties',
        '/chart-accounts',
        '/cash-accounts',
        '/income-records/create',
        '/expense-records/create',
        '/payments/create',
    ] as $path) {
        $this->get($path)->assertNotFound();
    }
});

test('authenticated users can still view the public welcome page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertSuccessful()->assertSee('Abrir panel');
});

test('authorized users can view the Filament dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => 'accountant']));

    $this->get('/admin')->assertSuccessful()->assertSee('ContPass');
});

test('viewer users cannot access the Filament panel', function () {
    $this->actingAs(User::factory()->create(['role' => 'viewer']));

    $this->get('/admin')->assertForbidden();
});

test('authorized users can render core Filament pages', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    foreach ([
        '/admin/third-parties',
        '/admin/chart-accounts',
        '/admin/cash-accounts',
        '/admin/withholding-rules',
        '/admin/income-records/create',
        '/admin/expense-records/create',
        '/admin/payments/create',
        '/admin/vouchers',
        '/admin/ledger-report',
        '/admin/third-party-movements-report',
        '/admin/trial-balance-report',
    ] as $path) {
        $this->get($path)->assertSuccessful();
    }
});
