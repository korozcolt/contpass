<?php

test('the welcome page presents ContPass and links to the admin panel', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('ContPass')
        ->assertSee('Arquitectura del sistema')
        ->assertSee('Mapa estructural del software')
        ->assertSee('images/brand/contpass-logo-horizontal.png', false)
        ->assertSee('images/brand/contpass-icon-32.png', false)
        ->assertSee('Sin datos sensibles')
        ->assertSee('/admin', false)
        ->assertDontSee('COP $')
        ->assertDontSee('Cliente Local');
});
