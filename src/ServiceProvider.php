<?php
namespace Siipis\CMS;

use TwigBridge\Engine\Twig as Engine;
use Siipis\CMS\Loader\Loader;
use Siipis\CMS\Parser\Parser;
use Siipis\CMS\Template\Scaffolding as Helper;
use TwigBridge\ServiceProvider as ViewServiceProvider;
use TwigBridge\Engine\Compiler;

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
        parent::register();

        $this->registerHelpers();
        $this->registerConsole();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../config/cms.php' => config_path('cms.php'),
        ]);
    }

    /**
     * Load the configuration files and allow them to be published.
     *
     * @return void
     */
    protected function loadConfiguration()
    {
        parent::loadConfiguration();

        $configPath = __DIR__ . '/../config/cms.php';

        $this->publishes([$configPath => config_path('cms.php')], 'config');

        $this->mergeConfigFrom($configPath, 'cms');
    }

    protected function registerExtension()
    {
        // Register as a Laravel view provider
        $this->app['view']->addExtension(
            $this->app['twig.extension'],
            'twig',
            function () {
                return $this->app['cms.engine'];
            }
        );
    }

    protected function registerLoaders()
    {
        parent::registerLoaders();

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
    }

    public function registerEngine()
    {
        parent::registerEngine();

        // Creates a custom Twig Environment
        $this->app->bindIf('cms', function () {
            $env = new CMS(
                $this->app['cms.loader'],
                $this->app['twig.options'],
                $this->app
            );

            $env->setBaseTemplateClass('CMS\Template\Template');

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

        $this->app->bindIf('cms.compiler', function () {
            return new Compiler($this->app['cms']);
        });

        // Configures a Twigbridge view engine to suit the CMS
        $this->app->bindIf('cms.engine', function () {
            return new Engine(
                $this->app['cms.compiler'],
                $this->app['cms.loader.filesystem'],
                $this->app['config']->get('twigbridge.twig.globals', [])
            );
        });
    }

    /**
     * Allow quick access for helper classes
     */
    protected function registerHelpers()
    {
        // Return as a singleton to keep data in sync
        $this->app->singleton('cms.helper', function () {
            return new Helper;
        });

        $this->app->bindIf('cms.parser', function () {
            return new Parser([
                'CMS\Parser\ConfigParser',
                'CMS\Parser\SourceParser',
            ]);
        });
    }

    /**
     * Registers Artisan console commands
     */
    protected function registerConsole()
    {
        $this->app->bindIf('command.make.data', function () {
            return new Console\MakeDataProvider($this->app['files'], $this->app['composer']);
        });

        $this->commands(
            'command.make.data'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cms.compiler',
            'cms.engine',
            'cms.helper',
            'cms.loader',
            'cms.loader.filesystem',
            'cms.parser',
            'cms',
        ];
    }
}