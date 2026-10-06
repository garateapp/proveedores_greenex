<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Correlativo;
use App\Models\IdempotencyKey;
use App\Models\TipoDocumento;
use App\Observers\AuditableObserver;
use App\Policies\TipoDocumentoPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(TipoDocumento::class, TipoDocumentoPolicy::class);

        $this->registerAuditableObservers();
    }

    /**
     * Modelos que existen solo para sostener la mecánica de GaratePass y cuya
     * traza no aporta nada: el correlativo se actualiza en cada vale emitido y
     * la clave de idempotencia se borra a las 24 horas. Auditarlos llenaría
     * audit_logs de filas que nadie va a consultar.
     *
     * @var list<class-string>
     */
    private array $modelosSinAuditoria = [
        AuditLog::class,
        Correlativo::class,
        IdempotencyKey::class,
    ];

    private function registerAuditableObservers(): void
    {
        $modelFiles = File::files(app_path('Models'));

        foreach ($modelFiles as $modelFile) {
            $className = 'App\\Models\\'.$modelFile->getFilenameWithoutExtension();

            if (! class_exists($className)) {
                continue;
            }

            if (! is_subclass_of($className, Model::class)) {
                continue;
            }

            if (in_array($className, $this->modelosSinAuditoria, true)) {
                continue;
            }

            $className::observe(AuditableObserver::class);
        }
    }
}
