<?php

use App\Models\User;

test('guests are redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users are sent to the Filament panel', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect('/admin');
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
