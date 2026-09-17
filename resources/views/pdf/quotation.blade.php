<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->number }}</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1c1917;
            font-size: 10pt;
            font-weight: 400;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            width: 100%;
        }

        .header-logo img {
            height: 32px;
        }

        .accent-rule {
            border-top: 2pt solid #D97706;
            margin-top: 8px;
            margin-bottom: 16px;
        }

        .title {
            font-size: 18pt;
            font-weight: 600;
            line-height: 1.2;
            color: #D97706;
        }

        .section {
            margin-bottom: 16px;
        }

        .section-heading {
            font-size: 12pt;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .parties-table td {
            vertical-align: top;
            width: 50%;
            padding-right: 8px;
        }

        .field-label {
            font-size: 9pt;
            font-weight: 600;
            line-height: 1.2;
            color: #57534e;
        }

        .field-value {
            font-size: 10pt;
            font-weight: 400;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .lines-table {
            margin-top: 16px;
        }

        .lines-table th {
            background-color: #F5F5F4;
            font-size: 9pt;
            font-weight: 600;
            line-height: 1.2;
            text-align: left;
            padding: 4px 8px;
        }

        .lines-table td {
            font-size: 10pt;
            font-weight: 400;
            line-height: 1.4;
            padding: 4px 8px;
            border-bottom: 0.5pt solid #E7E5E4;
        }

        .lines-table .text-right {
            text-align: right;
        }

        .totals-box {
            margin-top: 24px;
            background-color: #F5F5F4;
            padding: 24px;
            text-align: right;
        }

        .totals-box .total-amount {
            font-size: 18pt;
            font-weight: 600;
            line-height: 1.2;
            color: #D97706;
        }

        .notes-section {
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <table class="header-logo">
        <tr>
            <td>
                <img src="{{ public_path('images/brand/contpass-logo-horizontal.png') }}" alt="ContPass">
            </td>
        </tr>
    </table>

    <div class="accent-rule"></div>

    <div class="title">COTIZACIÓN {{ $quotation->number }}</div>

    <div class="section" style="margin-top: 32px;">
        <table class="parties-table">
            <tr>
                <td>
                    <div class="section-heading">Datos de la empresa</div>
                    <div class="field-label">Nombre</div>
                    <div class="field-value">{{ $quotation->company->name }}</div>
                    <div class="field-label">NIT</div>
                    <div class="field-value">{{ $quotation->company->tax_id }}</div>
                    <div class="field-label">Dirección</div>
                    <div class="field-value">{{ $quotation->company->address }}</div>
                    <div class="field-label">Teléfono</div>
                    <div class="field-value">{{ $quotation->company->phone }}</div>
                </td>
                <td>
                    <div class="section-heading">Datos del cliente</div>
                    <div class="field-label">Nombre</div>
                    <div class="field-value">{{ $quotation->thirdParty->name }}</div>
                    <div class="field-label">NIT/Cédula</div>
                    <div class="field-value">{{ $quotation->thirdParty->tax_id }}-{{ $quotation->thirdParty->verification_digit }}</div>
                    <div class="field-label">Vigencia hasta</div>
                    <div class="field-value">{{ $quotation->expires_on->format('Y-m-d') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="lines-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Valor unitario</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="text-right">{{ number_format((float) $line->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-box">
        <span class="total-amount">Total: ${{ number_format($quotation->total, 2) }}</span>
    </div>

    @if($quotation->notes)
        <div class="notes-section">
            <div class="section-heading">Términos y condiciones</div>
            <div class="field-value">{{ $quotation->notes }}</div>
        </div>
    @endif
</body>
</html>
