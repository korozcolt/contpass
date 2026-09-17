<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivipolaCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $payload = json_decode(
            file_get_contents(__DIR__.'/data/divipola.json') ?: '{}',
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $now = now();

        DB::table('departments')->upsert(
            collect($payload['departments'])->map(fn (array $department): array => [
                'code' => $department['code'],
                'name' => $department['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['code'],
            ['name', 'updated_at'],
        );

        $departmentIds = Department::query()->pluck('id', 'code');

        DB::table('municipalities')->upsert(
            collect($payload['municipalities'])->map(fn (array $municipality) => [
                'department_id' => $departmentIds[$municipality['department_code']],
                'code' => $municipality['code'],
                'name' => $municipality['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['department_id', 'code'],
            ['name', 'updated_at'],
        );
    }
}
