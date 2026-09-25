<?php

namespace Database\Seeders;

use App\Enums\AccountNature;
use App\Enums\BudgetCertificateStatus;
use App\Enums\BudgetModificationType;
use App\Enums\BudgetObligationStatus;
use App\Enums\BudgetRegistrationStatus;
use App\Enums\CashAccountType;
use App\Enums\CashProgramMovementType;
use App\Enums\CompanyType;
use App\Enums\EmployeeContractType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOrderStatus;
use App\Enums\PayrollConceptType;
use App\Enums\PayrollFundType;
use App\Enums\PettyCashMovementType;
use App\Enums\PublicEntityType;
use App\Enums\SignatoryArea;
use App\Enums\ThirdPartyType;
use App\Enums\UserRole;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Enums\WarehouseItemType;
use App\Enums\WarehouseMovementType;
use App\Enums\WithholdingType;
use App\Models\AccountingEntry;
use App\Models\AccountingPeriod;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetChartMapping;
use App\Models\BudgetModification;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\BudgetRevenue;
use App\Models\CashAccount;
use App\Models\CashProgramItem;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\CompanySignatory;
use App\Models\Dependency;
use App\Models\Employee;
use App\Models\ExpenseRecord;
use App\Models\IncomeRecord;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PayrollConcept;
use App\Models\PayrollFund;
use App\Models\PettyCashFund;
use App\Models\PettyCashMovement;
use App\Models\ThirdParty;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Models\WarehouseMovementLine;
use App\Models\WithholdingRule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AguasDeSucreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DivipolaCatalogSeeder::class);

        // 1. Usuarios del sistema
        $adminAguas = User::query()->updateOrCreate(
            ['email' => 'admin@aguasdesucre.com.co'],
            [
                'name' => 'Administrador Aguas de Sucre',
                'password' => Hash::make('AguasDeSucre2026*'),
                'role' => UserRole::Admin,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'contador@aguasdesucre.com.co'],
            [
                'name' => 'Carlos Montes (Contador)',
                'password' => Hash::make('AguasDeSucre2026*'),
                'role' => UserRole::Accountant,
            ]
        );

        // 2. Empresa: Aguas de Sucre S.A. E.S.P.
        // Si ya existe una empresa con ID 1 o tax_id 900000000, la transformamos; de lo contrario creamos/actualizamos.
        $company = Company::query()->oldest()->first();
        if ($company === null) {
            $company = new Company;
        }

        $company->fill([
            'name' => 'Aguas de Sucre S.A. E.S.P.',
            'tax_id' => '900247655',
            'verification_digit' => 1,
            'currency' => 'COP',
            'has_budgetary_control' => true,
            'type' => CompanyType::Public,
            'public_entity_type' => PublicEntityType::Esp,
            'phone' => '+57 (605) 276 3456',
            'email' => 'contacto@aguasdesucre.com.co',
            'address' => 'Carrera 18 # 20-45, Edificio San José, Piso 3',
            'city' => 'Sincelejo',
            'legal_representative' => 'Eduardo José Pérez Hernández',
            'dane_department_code' => '70',
            'dane_municipality_code' => '001',
        ]);
        $company->save();

        // 3. Periodo Contable 2026
        $period = AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->whereDate('starts_on', '2026-01-01')
            ->whereDate('ends_on', '2026-12-31')
            ->first();

        if (! $period) {
            AccountingPeriod::query()->create([
                'company_id' => $company->id,
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'is_closed' => false,
            ]);
        }

        // 4. Dependencias Institucionales
        $dependencias = [
            'Gerencia General',
            'Subgerencia de Gestión Técnica y Operativa (PDA)',
            'Dirección Administrativa y Financiera',
            'Oficina Asesora Jurídica',
            'Oficina de Control Interno y Calidad',
        ];
        $depModels = [];
        foreach ($dependencias as $depName) {
            $depModels[$depName] = Dependency::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => $depName],
                ['is_active' => true]
            );
        }

        // 5. Firmantes Autorizados
        $firmantes = [
            [
                'area' => SignatoryArea::LegalRepresentative,
                'full_name' => 'Eduardo José Pérez Hernández',
                'position' => 'Gerente General',
                'identification' => '73145892',
            ],
            [
                'area' => SignatoryArea::Budget,
                'full_name' => 'María Angélica Arrieta Paternina',
                'position' => 'Jefe de Presupuesto',
                'identification' => '64589123',
            ],
            [
                'area' => SignatoryArea::Accounting,
                'full_name' => 'Carlos Alberto Montes Vergara',
                'position' => 'Contador General (T.P. 128456-T)',
                'identification' => '92534890',
            ],
            [
                'area' => SignatoryArea::Treasury,
                'full_name' => 'Diana Marcela Vergara Chadid',
                'position' => 'Tesorera General',
                'identification' => '1102845671',
            ],
            [
                'area' => SignatoryArea::InternalControl,
                'full_name' => 'Andrés Felipe Paternina Ruiz',
                'position' => 'Jefe de Control Interno',
                'identification' => '92512344',
            ],
            [
                'area' => SignatoryArea::GeneralSecretary,
                'full_name' => 'Laura Patricia Chadid Gómez',
                'position' => 'Secretaria General',
                'identification' => '64532109',
            ],
        ];

        foreach ($firmantes as $f) {
            CompanySignatory::query()->updateOrCreate(
                ['company_id' => $company->id, 'area' => $f['area']],
                [
                    'full_name' => $f['full_name'],
                    'position' => $f['position'],
                    'identification' => $f['identification'],
                    'is_active' => true,
                ]
            );
        }

        // 6. Plan Único de Cuentas (PUC)
        $pucAccounts = [
            ['110505', 'Caja general sede administrativa', AccountNature::Debit],
            ['111005', 'Banco Agrario - Cta Cte Recursos SGP', AccountNature::Debit],
            ['111006', 'Bancolombia - Cta Cte Operación PDA', AccountNature::Debit],
            ['130505', 'Usuarios servicio acueducto y alcantarillado', AccountNature::Debit],
            ['138020', 'Cuentas por cobrar transferencias PDA Sucre', AccountNature::Debit],
            ['140501', 'Inventario materiales y tuberías acueducto', AccountNature::Debit],
            ['140502', 'Inventario reactivos químicos potabilización', AccountNature::Debit],
            ['220505', 'Proveedores nacionales bienes y servicios', AccountNature::Credit],
            ['220510', 'Contratistas de obra pública agua potable', AccountNature::Credit],
            ['233525', 'Servicios técnicos y operativos por pagar', AccountNature::Credit],
            ['233550', 'Servicio energía eléctrica bombeo por pagar', AccountNature::Credit],
            ['236540', 'Retención en la fuente por servicios 4%', AccountNature::Credit],
            ['236525', 'Retención en la fuente por compras 2.5%', AccountNature::Credit],
            ['236801', 'ReteICA Municipio de Sincelejo 7x1000', AccountNature::Credit],
            ['310505', 'Capital social suscrito y pagado entidades públicas', AccountNature::Credit],
            ['413530', 'Ingresos tarifarios venta de agua potable', AccountNature::Credit],
            ['414005', 'Transferencias SGP agua potable y saneamiento básico', AccountNature::Credit],
            ['510506', 'Sueldos y salarios personal de planta', AccountNature::Debit],
            ['511115', 'Mantenimiento y reparaciones redes acueducto', AccountNature::Debit],
            ['511120', 'Insumos químicos y reactivos de potabilización', AccountNature::Debit],
            ['511146', 'Energía eléctrica estaciones de bombeo', AccountNature::Debit],
            ['610505', 'Costos directos potabilización de agua', AccountNature::Debit],
        ];

        $chartModels = [];
        foreach ($pucAccounts as [$code, $name, $nature]) {
            $chartModels[$code] = ChartAccount::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                ['name' => $name, 'nature' => $nature, 'is_active' => true]
            );
        }

        // 7. Cuentas Financieras (Cajas y Bancos)
        $cashAccounts = [
            [
                'name' => 'Banco Agrario - CTA CTE Recursos SGP',
                'chart_code' => '111005',
                'type' => CashAccountType::Bank,
                'bank_name' => 'Banco Agrario de Colombia',
                'account_number' => '30010045892',
            ],
            [
                'name' => 'Bancolombia - CTA CTE Operación PDA',
                'chart_code' => '111006',
                'type' => CashAccountType::Bank,
                'bank_name' => 'Bancolombia',
                'account_number' => '52000891234',
            ],
            [
                'name' => 'Caja General Sede Sincelejo',
                'chart_code' => '110505',
                'type' => CashAccountType::Cash,
                'bank_name' => null,
                'account_number' => null,
            ],
        ];

        $cashModels = [];
        foreach ($cashAccounts as $ca) {
            $cashModels[$ca['name']] = CashAccount::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $ca['name']],
                [
                    'chart_account_id' => $chartModels[$ca['chart_code']]->id,
                    'type' => $ca['type'],
                    'bank_name' => $ca['bank_name'],
                    'account_number' => $ca['account_number'],
                    'is_active' => true,
                ]
            );
        }

        // 8. Reglas de Retención
        WithholdingRule::query()->updateOrCreate(
            ['company_id' => $company->id, 'type' => WithholdingType::ReteFuente, 'description' => 'Retención Servicios Generales 4%'],
            [
                'chart_account_id' => $chartModels['236540']->id,
                'minimum_base' => 150000,
                'rate' => 4.00,
                'starts_on' => '2026-01-01',
                'ends_on' => null,
                'is_active' => true,
            ]
        );

        WithholdingRule::query()->updateOrCreate(
            ['company_id' => $company->id, 'type' => WithholdingType::ReteFuente, 'description' => 'Retención Compras 2.5%'],
            [
                'chart_account_id' => $chartModels['236525']->id,
                'minimum_base' => 1100000,
                'rate' => 2.50,
                'starts_on' => '2026-01-01',
                'ends_on' => null,
                'is_active' => true,
            ]
        );

        // 9. Terceros Institucionales y Contratistas
        $tercerosData = [
            [
                'tax_id' => '892280020',
                'verification_digit' => 9,
                'name' => 'Gobernación del Departamento de Sucre',
                'email' => 'hacienda@sucre.gov.co',
                'phone' => '(605) 279 9000',
                'address' => 'Calle 25 # 25B-35, Palacio Departamental',
                'city' => 'Sincelejo',
            ],
            [
                'tax_id' => '800197268',
                'verification_digit' => 4,
                'name' => 'Dirección de Impuestos y Aduanas Nacionales - DIAN',
                'email' => 'recaudo@dian.gov.co',
                'phone' => '(601) 355 6922',
                'address' => 'Carrera 8 # 6C-38',
                'city' => 'Bogotá',
            ],
            [
                'tax_id' => '892280032',
                'verification_digit' => 1,
                'name' => 'Alcaldía Municipal de Sincelejo',
                'email' => 'contactenos@sincelejo.gov.co',
                'phone' => '(605) 274 0240',
                'address' => 'Calle 28 # 25A-24',
                'city' => 'Sincelejo',
            ],
            [
                'tax_id' => '900543210',
                'verification_digit' => 5,
                'name' => 'Químicos Industriales de la Costa S.A.S.',
                'email' => 'ventas@quimicoscosta.com.co',
                'phone' => '(605) 368 5500',
                'address' => 'Vía 40 # 73-290, Zona Industrial',
                'city' => 'Barranquilla',
            ],
            [
                'tax_id' => '901876543',
                'verification_digit' => 2,
                'name' => 'Consorcio Redes Sabanas 2026',
                'email' => 'consorcio.redes.sabanas@gmail.com',
                'phone' => '(605) 282 1190',
                'address' => 'Calle 20 # 19-30, Barrio Ford',
                'city' => 'Sincelejo',
            ],
            [
                'tax_id' => '892200015',
                'verification_digit' => 5,
                'name' => 'Caja de Compensación Familiar de Sucre - COMFASUCRE',
                'email' => 'aportes@comfasucre.com.co',
                'phone' => '(605) 279 9400',
                'address' => 'Calle 22 # 21-68',
                'city' => 'Sincelejo',
            ],
            [
                'tax_id' => '901380907',
                'verification_digit' => 0,
                'name' => 'Afinia - Grupo EPM (Caribemar de la Costa S.A.S. E.S.P.)',
                'email' => 'corporativo@afinia.com.co',
                'phone' => '(605) 385 0270',
                'address' => 'Centro Comercial San Jerónimo Local 201',
                'city' => 'Montería',
            ],
        ];

        $terceroModels = [];
        foreach ($tercerosData as $td) {
            $terceroModels[$td['tax_id']] = ThirdParty::query()->updateOrCreate(
                ['company_id' => $company->id, 'tax_id' => $td['tax_id']],
                [
                    'type' => ThirdPartyType::LegalEntity,
                    'verification_digit' => $td['verification_digit'],
                    'name' => $td['name'],
                    'email' => $td['email'],
                    'phone' => $td['phone'],
                    'address' => $td['address'],
                    'city' => $td['city'],
                ]
            );
        }

        // 10. Presupuesto de Ingresos 2026
        $ingresosPresupuestales = [
            [
                'code' => '1.1.01',
                'name' => 'Tarifas y Facturación de Venta de Agua Potable',
                'category' => 'corriente',
                'projected_amount' => 850000000.00,
            ],
            [
                'code' => '1.2.01',
                'name' => 'Transferencias Sistema General de Participaciones (SGP) Agua Potable',
                'category' => 'capital',
                'projected_amount' => 2400000000.00,
            ],
            [
                'code' => '1.3.01',
                'name' => 'Rendimientos Financieros Recursos PDA Sucre',
                'category' => 'fondos_especiales',
                'projected_amount' => 45000000.00,
            ],
        ];

        $budgetRevenueModels = [];
        foreach ($ingresosPresupuestales as $ip) {
            $budgetRevenueModels[$ip['code']] = BudgetRevenue::query()->updateOrCreate(
                ['company_id' => $company->id, 'fiscal_year' => 2026, 'code' => $ip['code']],
                [
                    'name' => $ip['name'],
                    'category' => $ip['category'],
                    'projected_amount' => $ip['projected_amount'],
                    'is_active' => true,
                ]
            );
        }

        // 11. Presupuesto de Gastos 2026 (Apropiaciones)
        $gastosPresupuestales = [
            [
                'code' => '2.1.1',
                'name' => 'Servicios Personales y Nómina de Planta',
                'initial_amount' => 420000000.00,
                'puc_expense' => '510506',
                'puc_payable' => '233525',
            ],
            [
                'code' => '2.1.2',
                'name' => 'Servicios Generales y Operación de Sede',
                'initial_amount' => 180000000.00,
                'puc_expense' => '511146',
                'puc_payable' => '233550',
            ],
            [
                'code' => '2.2.1',
                'name' => 'Adquisición de Insumos Químicos para Potabilización',
                'initial_amount' => 350000000.00,
                'puc_expense' => '511120',
                'puc_payable' => '220505',
            ],
            [
                'code' => '2.2.2',
                'name' => 'Mantenimiento Preventivo y Correctivo de Redes y Pozos',
                'initial_amount' => 620000000.00,
                'puc_expense' => '511115',
                'puc_payable' => '233525',
            ],
            [
                'code' => '2.3.1',
                'name' => 'Obras de Optimización de Acueducto Subregión Sabanas (PDA)',
                'initial_amount' => 1200000000.00,
                'puc_expense' => '610505',
                'puc_payable' => '220510',
            ],
        ];

        $apprModels = [];
        foreach ($gastosPresupuestales as $gp) {
            $appr = BudgetAppropriation::query()->updateOrCreate(
                ['company_id' => $company->id, 'fiscal_year' => 2026, 'code' => $gp['code']],
                [
                    'name' => $gp['name'],
                    'initial_amount' => $gp['initial_amount'],
                    'additions' => 0.00,
                    'reductions' => 0.00,
                    'is_active' => true,
                ]
            );
            $apprModels[$gp['code']] = $appr;

            // Mapeo Rubro -> PUC
            BudgetChartMapping::query()->updateOrCreate(
                ['company_id' => $company->id, 'budget_appropriation_id' => $appr->id],
                [
                    'expense_chart_account_id' => $chartModels[$gp['puc_expense']]->id,
                    'payable_chart_account_id' => $chartModels[$gp['puc_payable']]->id,
                ]
            );
        }

        // 12. Modificación Presupuestal: Adición por Acta de Junta Directiva
        $obrasAppr = $apprModels['2.3.1'];
        $existingMod = BudgetModification::query()->where('company_id', $company->id)
            ->where('document_reference', 'Acta de Junta Directiva N° 004 de 2026')
            ->first();

        if (! $existingMod) {
            BudgetModification::create([
                'company_id' => $company->id,
                'type' => BudgetModificationType::Addition,
                'document_reference' => 'Acta de Junta Directiva N° 004 de 2026',
                'source_appropriation_id' => null,
                'destination_appropriation_id' => $obrasAppr->id,
                'amount' => 200000000.00,
                'effective_date' => '2026-03-15',
                'justification' => 'Incorporación de mayores recursos de cofinanciación PDA para expansión de cobertura de agua en municipios de Sucre.',
                'user_id' => $adminAguas->id,
            ]);
        }

        // 13. Cadena de Gasto Real: Insumos Químicos (CDP -> RP -> Obligación -> OP -> Pago)
        $quimicosAppr = $apprModels['2.2.1'];
        $proveedorQuimicos = $terceroModels['900543210']; // Químicos Industriales de la Costa

        $cdp = BudgetAvailabilityCertificate::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'CDP-2026-000001'],
            [
                'budget_appropriation_id' => $quimicosAppr->id,
                'status' => BudgetCertificateStatus::Active,
                'fiscal_year' => 2026,
                'amount' => 85000000.00,
                'justification' => 'Suministro de hipoclorito de calcio y coagulantes para la potabilización de agua en municipios del PDA Sucre.',
                'issued_on' => '2026-02-10',
                'expires_on' => '2026-12-31',
            ]
        );

        $rp = BudgetRegistration::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'RP-2026-000001'],
            [
                'budget_availability_certificate_id' => $cdp->id,
                'third_party_id' => $proveedorQuimicos->id,
                'status' => BudgetRegistrationStatus::Active,
                'fiscal_year' => 2026,
                'amount' => 60000000.00,
                'justification' => 'Contrato N° AS-PDA-012-2026: Suministro periódico de reactivos químicos para tratamiento de agua potable.',
                'issued_on' => '2026-02-15',
            ]
        );

        // Comprobante contable de gasto causado (Obligación aprobada)
        $voucherGasto = Voucher::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COMP-OBL-2026-001'],
            [
                'third_party_id' => $proveedorQuimicos->id,
                'type' => VoucherType::Expense,
                'status' => VoucherStatus::Approved,
                'date' => '2026-03-05',
                'description' => 'Causación entrega mensual químicos potabilización Factura FQ-4589',
                'approved_at' => Carbon::parse('2026-03-05 10:30:00'),
            ]
        );

        // Líneas PUC: Débito Gasto 511120 / Crédito Proveedores 220505
        if ($voucherGasto->entries()->count() === 0) {
            AccountingEntry::query()->create([
                'voucher_id' => $voucherGasto->id,
                'chart_account_id' => $chartModels['511120']->id,
                'third_party_id' => $proveedorQuimicos->id,
                'description' => 'Gasto insumos químicos potabilización Factura FQ-4589',
                'debit' => 25000000.00,
                'credit' => 0.00,
            ]);
            AccountingEntry::query()->create([
                'voucher_id' => $voucherGasto->id,
                'chart_account_id' => $chartModels['220505']->id,
                'third_party_id' => $proveedorQuimicos->id,
                'description' => 'Causación cuenta por pagar Factura FQ-4589 Químicos Industriales',
                'debit' => 0.00,
                'credit' => 25000000.00,
            ]);

            // Detalle en ExpenseRecord
            ExpenseRecord::query()->create([
                'voucher_id' => $voucherGasto->id,
                'expense_account_id' => $chartModels['511120']->id,
                'payable_account_id' => $chartModels['220505']->id,
                'support_type' => 'Factura Electrónica',
                'support_number' => 'FQ-4589',
                'accrual_date' => '2026-03-05',
                'amount' => 25000000.00,
                'has_valid_support' => true,
                'is_deductible' => true,
            ]);
        }

        $obligation = BudgetObligation::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OBL-2026-000001'],
            [
                'budget_registration_id' => $rp->id,
                'voucher_id' => $voucherGasto->id,
                'status' => BudgetObligationStatus::Approved,
                'fiscal_year' => 2026,
                'amount' => 25000000.00,
                'support_type' => 'Factura Electrónica',
                'support_number' => 'FQ-4589',
                'accrual_date' => '2026-03-05',
                'approved_at' => Carbon::parse('2026-03-05 10:30:00'),
                'description' => 'Entrega mensual de químicos según acta de recibo a satisfacción.',
            ]
        );

        // Orden de Pago ejecutada
        $bancoOperacion = $cashModels['Bancolombia - CTA CTE Operación PDA'];
        $voucherPago = Voucher::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COMP-EGR-2026-001'],
            [
                'third_party_id' => $proveedorQuimicos->id,
                'type' => VoucherType::Payment,
                'status' => VoucherStatus::Approved,
                'date' => '2026-03-10',
                'description' => 'Pago transferencia Factura FQ-4589 Químicos Industriales',
                'approved_at' => Carbon::parse('2026-03-10 15:45:00'),
            ]
        );

        if ($voucherPago->entries()->count() === 0) {
            // Débito a Proveedor 220505 / Crédito a Banco 111006
            AccountingEntry::query()->create([
                'voucher_id' => $voucherPago->id,
                'chart_account_id' => $chartModels['220505']->id,
                'third_party_id' => $proveedorQuimicos->id,
                'description' => 'Cancelación pasivo proveedores Factura FQ-4589',
                'debit' => 25000000.00,
                'credit' => 0.00,
            ]);
            AccountingEntry::query()->create([
                'voucher_id' => $voucherPago->id,
                'chart_account_id' => $chartModels['111006']->id,
                'third_party_id' => $proveedorQuimicos->id,
                'description' => 'Egreso Bancolombia pago transferencia TRF-BANCOL-88912',
                'debit' => 0.00,
                'credit' => 25000000.00,
            ]);
        }

        $op = PaymentOrder::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OP-2026-000001'],
            [
                'budget_obligation_id' => $obligation->id,
                'cash_account_id' => $bancoOperacion->id,
                'voucher_id' => $voucherPago->id,
                'status' => PaymentOrderStatus::Paid,
                'amount' => 25000000.00,
                'method' => PaymentMethod::BankTransfer,
                'issued_on' => '2026-03-08',
                'paid_on' => '2026-03-10',
                'reference' => 'TRF-BANCOL-88912',
                'description' => 'Cancelación entrega insumos químicos potabilización marzo 2026.',
            ]
        );

        Payment::query()->firstOrCreate(
            ['reference' => 'TRF-BANCOL-88912'],
            [
                'cash_account_id' => $bancoOperacion->id,
                'voucher_id' => $voucherPago->id,
                'source_voucher_id' => $voucherGasto->id,
                'payment_order_id' => $op->id,
                'amount' => 25000000.00,
                'method' => PaymentMethod::BankTransfer,
                'paid_on' => '2026-03-10',
                'is_bancarized' => true,
                'reconciled_at' => Carbon::parse('2026-03-31'),
            ]
        );

        // 14. Recaudo Real de Ingreso (Transferencia SGP Gobernación de Sucre)
        $bancoSgp = $cashModels['Banco Agrario - CTA CTE Recursos SGP'];
        $gobernacion = $terceroModels['892280020'];
        $rubroSgp = $budgetRevenueModels['1.2.01'];

        $voucherIngreso = Voucher::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COMP-ING-2026-001'],
            [
                'third_party_id' => $gobernacion->id,
                'type' => VoucherType::Income,
                'status' => VoucherStatus::Approved,
                'date' => '2026-02-28',
                'description' => 'Recaudo Transferencia SGP Agua Potable Bimestre Ene-Feb 2026',
                'approved_at' => Carbon::parse('2026-02-28 17:00:00'),
            ]
        );

        if ($voucherIngreso->entries()->count() === 0) {
            // Débito a Banco Agrario 111005 / Crédito a Ingreso Transferencias SGP 414005
            AccountingEntry::query()->create([
                'voucher_id' => $voucherIngreso->id,
                'chart_account_id' => $chartModels['111005']->id,
                'third_party_id' => $gobernacion->id,
                'description' => 'Ingreso en Banco Agrario transferencia SGP Agua Potable',
                'debit' => 380000000.00,
                'credit' => 0.00,
            ]);
            AccountingEntry::query()->create([
                'voucher_id' => $voucherIngreso->id,
                'chart_account_id' => $chartModels['414005']->id,
                'third_party_id' => $gobernacion->id,
                'description' => 'Causación ingreso Transferencias SGP Agua Potable y Saneamiento',
                'debit' => 0.00,
                'credit' => 380000000.00,
            ]);

            IncomeRecord::query()->create([
                'voucher_id' => $voucherIngreso->id,
                'budget_revenue_id' => $rubroSgp->id,
                'revenue_account_id' => $chartModels['414005']->id,
                'receivable_account_id' => $chartModels['138020']->id,
                'support_number' => 'RES-GOB-SUCRE-089',
                'accrual_date' => '2026-02-28',
                'amount' => 380000000.00,
            ]);
        }

        // 15. Almacén Central de Aguas de Sucre
        $bodega = Warehouse::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bodega Central de Materiales y Repuestos Sincelejo'],
            [
                'location' => 'Zona Industrial Sincelejo - Vía Corozal',
                'is_active' => true,
            ]
        );

        $itemsData = [
            [
                'code' => 'TUB-PVC-3-RDE21',
                'name' => 'Tubería PVC Presión RDE 21 de 3 pulgadas (Tramo 6m)',
                'type' => WarehouseItemType::Consumable,
                'unit_of_measure' => 'Metro',
                'minimum_stock' => 100.00,
                'initial_qty' => 350.00,
                'cost' => 45000.00,
            ],
            [
                'code' => 'REACT-HIPOCLOR-70',
                'name' => 'Hipoclorito de Calcio granular al 70% para potabilización',
                'type' => WarehouseItemType::Consumable,
                'unit_of_measure' => 'Cuñete 45Kg',
                'minimum_stock' => 20.00,
                'initial_qty' => 50.00,
                'cost' => 320000.00,
            ],
            [
                'code' => 'VALV-COMP-3-HF',
                'name' => 'Válvula de Compuerta vástago fijo 3" hierro fundido',
                'type' => WarehouseItemType::Returnable,
                'unit_of_measure' => 'Unidad',
                'minimum_stock' => 5.00,
                'initial_qty' => 18.00,
                'cost' => 680000.00,
            ],
            [
                'code' => 'MED-VOL-12-R160',
                'name' => 'Micromedidor volumétrico chorro único 1/2" clase R160',
                'type' => WarehouseItemType::Returnable,
                'unit_of_measure' => 'Unidad',
                'minimum_stock' => 50.00,
                'initial_qty' => 200.00,
                'cost' => 115000.00,
            ],
        ];

        $movimientoEntrada = WarehouseMovement::query()->firstOrCreate(
            ['company_id' => $company->id, 'number' => 'ENT-2026-000001'],
            [
                'warehouse_id' => $bodega->id,
                'third_party_id' => $proveedorQuimicos->id,
                'type' => WarehouseMovementType::Entry,
                'date' => '2026-02-20',
                'description' => 'Entrada inicial de inventario para mantenimiento de acueductos municipales 2026.',
            ]
        );

        foreach ($itemsData as $it) {
            $itemModel = WarehouseItem::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $it['code']],
                [
                    'name' => $it['name'],
                    'type' => $it['type'],
                    'unit_of_measure' => $it['unit_of_measure'],
                    'minimum_stock' => $it['minimum_stock'],
                    'is_active' => true,
                ]
            );

            WarehouseMovementLine::query()->firstOrCreate(
                [
                    'warehouse_movement_id' => $movimientoEntrada->id,
                    'warehouse_item_id' => $itemModel->id,
                ],
                [
                    'quantity' => $it['initial_qty'],
                    'unit_cost' => $it['cost'],
                ]
            );
        }

        // 16. Nómina Básica y Empleados
        $eps = PayrollFund::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Nueva EPS S.A.'],
            ['type' => PayrollFundType::Health, 'nit' => '900156264', 'is_active' => true]
        );

        $afp = PayrollFund::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Porvenir S.A.'],
            ['type' => PayrollFundType::Pension, 'nit' => '800144331', 'is_active' => true]
        );

        PayrollFund::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Positiva Compañía de Seguros'],
            ['type' => PayrollFundType::Arl, 'nit' => '860011153', 'is_active' => true]
        );

        PayrollConcept::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEV-001'],
            ['name' => 'Sueldo Básico Mensual', 'type' => PayrollConceptType::Earning, 'is_active' => true]
        );

        PayrollConcept::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DED-001'],
            ['name' => 'Aporte Salud 4%', 'type' => PayrollConceptType::Deduction, 'is_active' => true]
        );

        PayrollConcept::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DED-002'],
            ['name' => 'Aporte Pensión 4%', 'type' => PayrollConceptType::Deduction, 'is_active' => true]
        );

        $gerenciaDep = $depModels['Gerencia General'];
        $tecnicaDep = $depModels['Subgerencia de Gestión Técnica y Operativa (PDA)'];

        $empleados = [
            [
                'tax_id' => '73145892',
                'verification_digit' => 4,
                'name' => 'Eduardo José Pérez Hernández',
                'position' => 'Gerente General',
                'dependency_id' => $gerenciaDep->id,
                'contract_type' => EmployeeContractType::Indefinite,
                'hire_date' => '2024-01-02',
                'base_salary' => 14500000.00,
            ],
            [
                'tax_id' => '92534890',
                'verification_digit' => 2,
                'name' => 'Carlos Alberto Montes Vergara',
                'position' => 'Contador General',
                'dependency_id' => $depModels['Dirección Administrativa y Financiera']->id,
                'contract_type' => EmployeeContractType::Indefinite,
                'hire_date' => '2024-02-01',
                'base_salary' => 6800000.00,
            ],
            [
                'tax_id' => '1102845671',
                'verification_digit' => 8,
                'name' => 'Ing. Rafael David Sierra Méndez',
                'position' => 'Ingeniero de Operaciones de Acueducto',
                'dependency_id' => $tecnicaDep->id,
                'contract_type' => EmployeeContractType::Indefinite,
                'hire_date' => '2024-03-15',
                'base_salary' => 5200000.00,
            ],
        ];

        $empModels = [];
        foreach ($empleados as $emp) {
            $empModels[$emp['tax_id']] = Employee::query()->updateOrCreate(
                ['company_id' => $company->id, 'tax_id' => $emp['tax_id']],
                [
                    'dependency_id' => $emp['dependency_id'],
                    'pension_fund_id' => $afp->id,
                    'health_fund_id' => $eps->id,
                    'verification_digit' => $emp['verification_digit'],
                    'name' => $emp['name'],
                    'position' => $emp['position'],
                    'contract_type' => $emp['contract_type'],
                    'hire_date' => $emp['hire_date'],
                    'base_salary' => $emp['base_salary'],
                    'is_active' => true,
                ]
            );
        }

        // 17. Fondo de Caja Menor
        $custodio = $empModels['92534890']; // Contador Carlos Montes
        $cajaGeneral = $cashModels['Caja General Sede Sincelejo'];

        $fondoCajaMenor = PettyCashFund::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Caja Menor Sede Administrativa Sincelejo'],
            [
                'employee_id' => $custodio->id,
                'cash_account_id' => $cajaGeneral->id,
                'authorized_amount' => 3000000.00,
                'is_active' => true,
            ]
        );

        PettyCashMovement::query()->firstOrCreate(
            [
                'petty_cash_fund_id' => $fondoCajaMenor->id,
                'support_number' => 'REC-APERT-2026-01',
            ],
            [
                'type' => PettyCashMovementType::Opening,
                'date' => '2026-01-15',
                'amount' => 3000000.00,
                'description' => 'Apertura de fondo fijo caja menor para gastos urgentes y menores vigencia 2026.',
            ]
        );

        PettyCashMovement::query()->firstOrCreate(
            [
                'petty_cash_fund_id' => $fondoCajaMenor->id,
                'support_number' => 'FAC-PAPEL-089',
            ],
            [
                'third_party_id' => $terceroModels['892280032']->id,
                'type' => PettyCashMovementType::Expense,
                'date' => '2026-02-18',
                'amount' => 280000.00,
                'description' => 'Compra de resmas de papel, tóner y útiles de oficina para radicación.',
            ]
        );

        // 18. Programación Anual de Caja (P.A.C.)
        for ($m = 1; $m <= 6; $m++) {
            CashProgramItem::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'fiscal_year' => 2026,
                    'movement_type' => CashProgramMovementType::Expense,
                    'budget_appropriation_id' => $quimicosAppr->id,
                    'month' => $m,
                ],
                [
                    'projected_amount' => 35000000.00,
                ]
            );

            CashProgramItem::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'fiscal_year' => 2026,
                    'movement_type' => CashProgramMovementType::Income,
                    'budget_revenue_id' => $rubroSgp->id,
                    'month' => $m,
                ],
                [
                    'projected_amount' => 200000000.00,
                ]
            );
        }
    }
}
