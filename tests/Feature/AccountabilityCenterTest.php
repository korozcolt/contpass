<?php

use App\Filament\Pages\AccountabilityCenter;
use App\Models\User;
use Livewire\Livewire;

it('renders the accountability center for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(AccountabilityCenter::class)->assertSuccessful();
});

it('requires authentication to access the accountability center', function () {
    $this->get(AccountabilityCenter::getUrl())->assertRedirect();
});
