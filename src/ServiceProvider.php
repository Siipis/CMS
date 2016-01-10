<?php
namespace CMS;

use TwigBridge\Engine\Twig as Engine;
use CMS\Loader\Loader;
use CMS\Template\Scaffolding as Helper;
use Illuminate\View\ViewServiceProvider;

/**
 * Bootstrap CMS.
 *
 * You need to include this `ServiceProvider` in your app.php file:
 *
 * <code>
 *     'providers' => [
 *         'CMS\ServiceProvider'
 *     ];
 * </code>
 */
class ServiceProvider extends ViewServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerHelpers();
        $this->registerEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadConfiguration();
    }

    /**
     * Load the configuration files and allow them to be published.
     *
     * @return void
     */
    protected function loadConfiguration()
    {
        $configPath = __DIR__ . '/../config/cms.php';

        $this->publishes([$configPath => config_path('cms.php')], 'config');

        $this->mergeConfigFrom($configPath, 'cms');
    }

    protected function registerHelpers()
    {
        $this->app->singleton('cms.helper', function() {
            return new Helper;
        });

        $this->app->bindIf('cms.loader.filesystem', function () {
            return new Loader(
                $this->app['files'],
                $this->app['view']->getFinder(),
                $this->app['twig.extension']
            );
        });

        $this->app->bindIf('cms.loader', function () {
                return new \Twig_Loader_Chain([
                    $this->app['cms.loader.filesystem'],
                ]);
            },
            true
        );

        $this->app->bindIf('cms.engine', function () {
            return new Engine(
                $this->app['twig.compiler'],
                $this->app['cms.loader.filesystem'],
                $this->app['config']->get('twigbridge.twig.globals', [])
            );
        });
    }

    /**
     * Register CMS environment
     *
     * @return void
     */
    protected function registerEnvironment()
    {
        $this->app['view']->addExtension(
            $this->app['twig.extension'],
            'cms',
            function () {
                return $this->app['cms.engine'];
            }
        );


        $this->app->bindIf('cms', function() {
            $env = new CMS(
                $this->app['cms.loader'],
                $this->app['twig.options'],
                $this->app
            );
            
            // Instantiate and add Twig extensions
            $extensions = array_merge($this->app['twig.extensions'], [
                'CMS\Extension\Core',
            ]);

            foreach ($extensions as $extension) {
                // Get an instance of the extension
                // Support for string, closure and an object
                if (is_string($extension)) {
                    try {
                        $extension = $this->app->make($extension);
                    } catch (\Exception $e) {
                        throw new \InvalidArgumentException(
                            "Cannot instantiate Twig extension '$extension': " . $e->getMessage()
                        );
                    }
                } elseif (is_callable($extension)) {
                    $extension = $extension($this->app, $env);
                } elseif (!is_a($extension, 'Twig_Extension')) {
                    throw new \InvalidArgumentException('Incorrect extension type');
                }

                $env->addExtension($extension);
            }

            return $env;
        });

        $this->app->alias('cms', 'CMS\CMS');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cms.engine',
            'cms.helper',
            'cms.loader',
            'cms',
        ];
    }
}