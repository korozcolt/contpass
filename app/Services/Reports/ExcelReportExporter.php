<?php

namespace App\Services\Reports;

use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelReportExporter
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function handle(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $dateStyle = (new Style)->setFormat('dd/mm/yyyy');

            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $cells = array_map(
                    fn (mixed $value): Cell => Cell::fromValue(
                        $value,
                        $value instanceof DateTimeInterface ? $dateStyle : null,
                    ),
                    $row,
                );
                $writer->addRow(new Row($cells));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
