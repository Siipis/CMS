<?php
namespace CMS;

use App;
use Illuminate\Contracts\Foundation\Application;
use TwigBridge\Bridge as TwigBridge;
use Twig_LoaderInterface;
use View;

class CMS extends TwigBridge
{
    protected $routeCache;

    public function __construct(Twig_LoaderInterface $loader, $options = [], Application $app = null)
    {
        parent::__construct($loader, $options, $app);

        $this->routeCache = [];
    }

    /**
     * Renders a route
     *
     * @param $route
     * @param bool $isPage
     * @return bool|string
     */
    public function route($route, $isPage = true)
    {
        if ($view = $this->getViewName($route, $isPage))
        {
            return $this->render($view);
        }

        return false;
    }

    /**
     * Alias for route()
     */
    public function view($view, $isPage = true)
    {
        return $this->route($view, $isPage);
    }

    /**
     * Renders a route with variables
     *
     * @param string $name
     * @param array $context
     * @return bool|string
     */
    public function render($name, array $context = array())
    {
        if ($view = $this->getViewName($name))
        {
            return parent::render($view, $context);
        }

        return false;
    }

    /**
     * Returns the actual template path
     *
     * @param $name
     * @param bool $isPage
     * @return bool|string
     */
    protected function getViewName($name, $isPage = true)
    {
        $pageDir = config('cms.path.pages') .'/';

        if (!$isPage || starts_with($name, $pageDir)) {
            return $name;
        }

        if ($this->isCached($name)) {
            return $this->getCached($name);
        }

        if (View::exists($route = $pageDir . $name)) {
            $this->setCached($name, $route);

            return $route;
        }

        if (View::exists($route = $pageDir . $name .'/main')) {
            $this->setCached($name, $route);

            return $route;
        }

        abort(404);
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | View cache
    |--------------------------------------------------------------------------
    |
    | For faster routing and to avoid misrouting
    |
    */
    protected function isCached($name)
    {
        return isset($this->routeCache[$name]);
    }

    protected function getCached($name)
    {
        return $this->routeCache[$name];
    }

    protected function setCached($name, $route)
    {
        $this->routeCache[$name] = $route;
    }
}