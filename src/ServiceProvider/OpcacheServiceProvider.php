<?php declare(strict_types=1);

namespace TinyFramework\Opcache\ServiceProvider;

use TinyFramework\Core\ConfigInterface;
use TinyFramework\Http\Router;
use TinyFramework\Opcache\Console\Commands\TinyframeworkOpcacheClearCommand;
use TinyFramework\Opcache\Console\Commands\TinyframeworkOpcachePreloadCommand;
use TinyFramework\Opcache\Console\Commands\TinyframeworkOpcacheStatusCommand;
use TinyFramework\Opcache\Http\Middleware\OpcacheMiddleware;
use TinyFramework\ServiceProvider\ServiceProviderAwesome;
use TinyFramework\Opcache\Http\Controllers\OpcacheController;

class OpcacheServiceProvider extends ServiceProviderAwesome
{

    public function register(): void
    {
        $this->container->tag([
            'commands',
        ], [
            TinyframeworkOpcacheClearCommand::class,
            TinyframeworkOpcachePreloadCommand::class,
            TinyframeworkOpcacheStatusCommand::class,
        ]);

        $config = $this->container->get(ConfigInterface::class);
        if ($config->get('opcache.preloads') === null) {
            $config->load('opcache', __DIR__ . '/../Config/opcache.php');
        }
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        $router->group(['middleware' => OpcacheMiddleware::class], function (Router $router) {
            $router->post('__opcache/status', OpcacheController::class . '@status')->name('opcache.status');
            $router->post('__opcache/preload', OpcacheController::class . '@preload')->name('opcache.preload');
            $router->post('__opcache/clear', OpcacheController::class . '@clear')->name('opcache.clear');
        });
    }

}
