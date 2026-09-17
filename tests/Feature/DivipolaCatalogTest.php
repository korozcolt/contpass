<?php

use App\Enums\WithholdingType;
use App\Models\Department;
use App\Models\Municipality;
use Database\Seeders\DivipolaCatalogSeeder;

it('seeds the full divipola catalog with referential integrity', function () {
    (new DivipolaCatalogSeeder)->run();

    $municipalityCount = Municipality::count();
    $departmentCount = Department::count();

    expect($departmentCount)->toBeGreaterThanOrEqual(32)
        ->and($municipalityCount)->toBeGreaterThan(1000)
        ->and(Municipality::whereDoesntHave('department')->count())->toBe(0);
});

it('resolves a known municipality by department and municipality code', function () {
    (new DivipolaCatalogSeeder)->run();

    $medellin = Municipality::query()
        ->where('code', '001')
        ->whereHas('department', fn ($query) => $query->where('code', '05'))
        ->first();

    expect($medellin)->not->toBeNull()
        ->and($medellin->name)->toContain('Medell')
        ->and($medellin->department->name)->toContain('Antioquia');
});

it('exposes the three withholding type cases with labels, colors and icons', function () {
    expect(WithholdingType::cases())->toHaveCount(3);

    foreach (WithholdingType::cases() as $case) {
        expect($case->getLabel())->not->toBeEmpty()
            ->and($case->getColor())->not->toBeEmpty();
    }

    expect(WithholdingType::Ica->getLabel())->toBe('ICA');
});
