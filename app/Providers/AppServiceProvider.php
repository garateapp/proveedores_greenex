<?php

namespace App\Providers;

use App\Models\AuditLog;
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

            if ($className === AuditLog::class) {
                continue;
            }

            $className::observe(AuditableObserver::class);
        }
    }
}
