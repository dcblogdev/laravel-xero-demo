<?php

declare(strict_types=1);

use App\Livewire\Admin\Roles\Create;
use App\Models\Role;
use Livewire\Livewire;

beforeEach(function () {
    $this->authenticate();
});

test('can create role', function () {
    Livewire::test(Create::class)
        ->set('label', 'Editor')
        ->call('store')
        ->assertHasNoErrors(['role' => 'required']);

    expect(Role::where('name', 'editor')->exists())->toBeTrue();
});

test('cannot create role without role', function () {
    Livewire::test(Create::class)
        ->set('label', '')
        ->call('store')
        ->assertHasErrors(['label' => 'required']);
});

test('Can dispatch after role creation', function () {
    Livewire::test(Create::class)
        ->set('label', 'Editor')
        ->call('store')
        ->assertDispatched('added');
});
