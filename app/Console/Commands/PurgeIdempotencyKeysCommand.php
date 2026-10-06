<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PurgeIdempotencyKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'garatepass:purge-idempotency-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina las claves de idempotencia de GaratePass con más de 24 horas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ttlHoras = (int) config('garatepass.idempotency_ttl_horas', 24);

        $cortadas = IdempotencyKey::query()
            ->where('created_at', '<', now()->subHours($ttlHoras))
            ->delete();

        $this->info("Claves de idempotencia eliminadas: {$cortadas}");

        return self::SUCCESS;
    }
}
