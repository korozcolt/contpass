<?php

namespace App\Console\Commands;

use App\Services\Imports\ArchiveMasterPreviewImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contpass:import-archive-master-preview
    {path : Ruta absoluta o relativa del archivo JSON exportado desde Archive Master}
    {--commit : Persiste los datos. Sin esta opción se ejecuta en dry-run.}')]
#[Description('Importa una muestra fiscal de Archive Master a ContPass con validación de partida doble.')]
class ImportArchiveMasterPreview extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ArchiveMasterPreviewImporter $importer): int
    {
        $path = (string) $this->argument('path');
        $dryRun = ! $this->option('commit');

        if (! is_file($path)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            $this->error("No se encontró el archivo JSON: {$path}");

            return self::FAILURE;
        }

        $summary = $importer->import($path, $dryRun);

        $this->components->info($dryRun ? 'Dry-run completado. No se persistieron cambios.' : 'Importación persistida.');
        $this->table(['Métrica', 'Valor'], [
            ['Empresa', $summary['company']],
            ['PUC procesado', $summary['chart_accounts']],
            ['Terceros procesados', $summary['third_parties']],
            ['Cajas/bancos inferidos', $summary['cash_accounts']],
            ['Periodos procesados', $summary['periods']],
            ['Comprobantes importados', $summary['vouchers_imported']],
            ['Comprobantes ya existentes', $summary['vouchers_skipped']],
            ['Comprobantes rechazados', $summary['vouchers_rejected']],
        ]);

        if ($summary['rejected'] !== []) {
            $this->warn('Comprobantes rechazados:');
            $this->table(['ID', 'Número', 'Motivo'], $summary['rejected']);
        }

        return self::SUCCESS;
    }
}
