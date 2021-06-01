<?php declare(strict_types=1);

namespace TinyFramework\Opcache\ServiceProvider;

use TinyFramework\Core\ConfigInterface;
use TinyFramework\Http\Router;
use TinyFramework\Opcache\Commands\TinyframeworkOpcacheClearCommand;
use TinyFramework\Opcache\Commands\TinyframeworkOpcachePreloadCommand;
use TinyFramework\Opcache\Commands\TinyframeworkOpcacheStatusCommand;
use TinyFramework\Opcache\Http\Middleware\OpcacheMiddlewaere;
use TinyFramework\ServiceProvider\ServiceProviderAwesome;
use TinyFramework\Opcache\Http\Controllers\OpcacheController;

class OpcacheServiceProvider extends ServiceProviderAwesome
{

    public function register(): void
    {
        $this->container->tag([
            'commands'
        ], [
            TinyframeworkOpcacheClearCommand::class,
            TinyframeworkOpcachePreloadCommand::class,
            TinyframeworkOpcacheStatusCommand::class,
        ]);

        /** @var ConfigInterface $config */
        $config = $this->container->get(ConfigInterface::class);
        if ($config->get('opcache.preloads') === null) {
            $config->load('opcache', __DIR__ . '/../Config/opcache.php');
        }

        /** @var Router $router */
        $router = $this->container->get(Router::class);
        $router->group(['middleware' => OpcacheMiddlewaere::class], function (Router $router) {
            $router->post('__opcache/status', OpcacheController::class . '@status')->name('opcache.status');
            $router->post('__opcache/preload', OpcacheController::class . '@preload')->name('opcache.preload');
            $router->post('__opcache/reset', OpcacheController::class . '@reset')->name('opcache.reset');
        });
    }

}
