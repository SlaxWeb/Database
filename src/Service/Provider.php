<?php
/**
 * Database Component Service Provider
 *
 * The Database Component Service Provider registers the Model Loader service to
 * the DIC.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Service;

use Pimple\Container;

class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register Provider
     *
     * Called when the container is about to register this provider with the DIC.
     * It should define all the services, or call other methods that define the
     * services.
     *
     * @param \Pimple\Container $container Dependency Injection Container
     * @return void
     */
    public function register(Container $container)
    {
        $container["loadModel.service"] = $container->protect(
            function (string $model) use ($container) {
                $class = rtrim($container["config.service"]["database.classNamespace"], "\\")
                    . "\\"
                    . str_replace("/", "\\", $model);
                $model = new $model(
                    $container["logger.service"](),
                    $container["config.service"],
                    ICanBoogie\Inflector::get(),
                    $container["databaseLibrary.service"]
                );

                if (method_exists($model, "init")) {
                    $args = func_get_args();
                    array_shift($args);
                    $model->init(...$args);
                }

                return $model;
            }
        );
    }
}
