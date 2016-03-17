<?php
namespace CMS;

use App;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\Builder;
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
     * Views a page
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return ViewFactory|\Illuminate\Contracts\View\View
     */
    public function view($view, $data = [], $mergeData = [])
    {
        $factory = app(ViewFactory::class);

        return $factory->make($this->getViewName($view), $data, $mergeData);
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
        $view = $this->getViewName($name, false);

        return parent::render($view, $context);
    }

    /**
     * Returns the actual template path
     *
     * @param $name
     * @param bool $isPage
     * @return bool|string
     */
    public function getViewName($name, $isPage = true)
    {
        $pageDir = config('cms.path.pages') . '/';

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

        if (View::exists($route = $pageDir . $name . '/main')) {
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
    | For faster routing
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