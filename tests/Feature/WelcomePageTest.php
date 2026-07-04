<?php

test('the welcome page presents ContPass and links to the admin panel', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('ContPass')
        ->assertSee('Arquitectura del sistema')
        ->assertSee('Mapa estructural del software')
        ->assertSee('Sin datos sensibles')
        ->assertSee('/admin', false)
        ->assertDontSee('COP $')
        ->assertDontSee('Cliente Local');
});
