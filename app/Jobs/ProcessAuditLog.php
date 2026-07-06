<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected array $payload
    ) {}

    /**
     * Get the payload of the audit log.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Enriquecer el log con metadatos de tiempo de ejecución en el worker
        $this->payload['processed_at'] = now()->toIso8601String();

        Log::channel('audit')->info('Audit transaction recorded', $this->payload);
    }
}
