<?php

namespace Jeryseika\PdParser;

use Illuminate\Support\ServiceProvider;
use Jeryseika\PdParser\Services\StorageService;
use Jeryseika\PdParser\Services\PackService;
use Jeryseika\PdParser\Services\ConsoleService;

class PdParserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once __DIR__ . '/Support/helpers.php';

        $this->mergeConfigFrom(__DIR__ . '/../config/pd-parser.php', 'pd-parser');

        $this->app->singleton(StorageService::class);
        $this->app->singleton(PackService::class);
        $this->app->singleton(ConsoleService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pd');

        \Illuminate\Support\Facades\Blade::directive('pdicon', function (string $expression) {
            return "<?php echo \\Jeryseika\\PdParser\\Support\\PdIcon::svg({$expression}); ?>";
        });

        $this->publishes([
            __DIR__ . '/../config/pd-parser.php' => config_path('pd-parser.php'),
        ], 'pd-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/pd'),
        ], 'pd-views');

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    }
}
