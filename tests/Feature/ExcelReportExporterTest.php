<?php

use App\Services\Reports\ExcelReportExporter;
use Carbon\Carbon;
use OpenSpout\Reader\XLSX\Reader;

/**
 * @param  array<int, string>  $headers
 * @param  array<int, array<int, mixed>>  $rows
 */
function writeAndReopenXlsx(array $headers, array $rows, string $filename = 'test.xlsx'): array
{
    $response = app(ExcelReportExporter::class)->handle($filename, $headers, $rows);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    $tempPath = tempnam(sys_get_temp_dir(), 'xlsx-test-').'.xlsx';
    file_put_contents($tempPath, $content);

    $reader = new Reader();
    $reader->open($tempPath);

    $sheetRows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheetRows[] = $row->toArray();
        }
        break;
    }

    $reader->close();
    unlink($tempPath);

    return ['response' => $response, 'rows' => $sheetRows];
}

it('writes int/float values as native numeric cells', function () {
    $result = writeAndReopenXlsx(
        ['Nombre', 'Valor'],
        [['Fila 1', 1234.56], ['Fila 2', 10]],
    );

    $firstDataRow = $result['rows'][1];
    $secondDataRow = $result['rows'][2];

    expect($firstDataRow[1])->toBeFloat()->toBe(1234.56);
    expect(is_float($secondDataRow[1]) || is_int($secondDataRow[1]))->toBeTrue();
});

it('writes DateTimeInterface values as native date cells formatted dd/mm/yyyy', function () {
    $result = writeAndReopenXlsx(
        ['Nombre', 'Fecha'],
        [['Fila 1', Carbon::parse('2026-03-15')]],
    );

    $dataRow = $result['rows'][1];

    expect($dataRow[1])->toBeInstanceOf(DateTimeInterface::class);
    expect($dataRow[1]->format('Y-m-d'))->toBe('2026-03-15');
});

it('writes string and null values as text/empty cells, not corrupting the row', function () {
    $result = writeAndReopenXlsx(
        ['Nombre', 'Descripcion'],
        [['Descripción larga', null]],
    );

    $dataRow = $result['rows'][1];

    expect($dataRow[0])->toBe('Descripción larga');
    expect(empty($dataRow[1]))->toBeTrue();
});

it('response has the correct content-type and filename', function () {
    $response = app(ExcelReportExporter::class)->handle('test.xlsx', ['Nombre'], [['Fila 1']]);

    expect($response->headers->get('content-type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))
        ->toContain('test.xlsx');
});
