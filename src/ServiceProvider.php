<?php
namespace CMS;

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
        $this->registerFacades();
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
     * Register CMS facades
     *
     * @return void
     */
    protected function registerFacades()
    {
        $this->app->bind('cms', function() {
            return new \CMS\CMS;
        });

        $this->app->bind('cms.cache', function() {
            return new \CMS\Cache;
        });
    }
}