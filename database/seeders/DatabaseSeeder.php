<?php

namespace Database\Seeders;

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\UserRole;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\User;
use App\Models\WithholdingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrador', 'password' => Hash::make('password'), 'role' => UserRole::Admin],
        );

        $company = Company::query()->firstOrCreate(
            ['tax_id' => '900000000'],
            ['name' => 'Empresa Principal', 'verification_digit' => 9, 'currency' => 'COP'],
        );

        $accounts = [
            ['110505', 'Caja general', AccountNature::Debit],
            ['111005', 'Bancos nacionales', AccountNature::Debit],
            ['130505', 'Clientes nacionales', AccountNature::Debit],
            ['220505', 'Proveedores nacionales', AccountNature::Credit],
            ['236540', 'Retención en la fuente por servicios', AccountNature::Credit],
            ['413595', 'Ingresos operacionales', AccountNature::Credit],
            ['513525', 'Servicios', AccountNature::Debit],
        ];

        foreach ($accounts as [$code, $name, $nature]) {
            ChartAccount::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                ['name' => $name, 'nature' => $nature, 'is_active' => true],
            );
        }

        CashAccount::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Banco principal'],
            [
                'chart_account_id' => ChartAccount::query()->whereBelongsTo($company)->where('code', '111005')->value('id'),
                'type' => CashAccountType::Bank,
                'bank_name' => 'Banco',
                'account_number' => '000000001',
                'is_active' => true,
            ],
        );

        WithholdingRule::query()->firstOrCreate(
            ['company_id' => $company->id, 'concept' => 'Servicios 2026'],
            [
                'chart_account_id' => ChartAccount::query()->whereBelongsTo($company)->where('code', '236540')->value('id'),
                'minimum_base' => 100000,
                'rate' => 4,
                'starts_on' => '2026-01-01',
                'ends_on' => null,
                'is_active' => true,
            ],
        );
    }
}
