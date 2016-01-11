<?php
namespace CMS;

use TwigBridge\Engine\Twig as Engine;
use CMS\Loader\Loader;
use CMS\Parser\Parser;
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

    /**
     * Allow quick access for helper classes
     */
    protected function registerHelpers()
    {
        // Return as a singleton to keep data in sync
        $this->app->singleton('cms.helper', function() {
            return new Helper;
        });

        // Loads the template and config
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

        $this->app->bindIf('cms.parser', function() {
            return new Parser([
                'CMS\Parser\ConfigParser',
                'CMS\Parser\SourceParser',
            ]);
        });

        // Configures a Twigbridge view engine to suit the CMS
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
        // Register as a Laravel view provider
        $this->app['view']->addExtension(
            $this->app['twig.extension'],
            'cms',
            function () {
                return $this->app['cms.engine'];
            }
        );

        // Creates a custom Twig Environment
        $this->app->singleton('cms', function() {
            $env = new CMS(
                $this->app['cms.loader'],
                $this->app['twig.options'],
                $this->app
            );

            /**
             * Registers the Twig extensions
             */
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
            'cms.parser',
            'cms',
        ];
    }
}