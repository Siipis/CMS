<?php
namespace CMS;

use App;
use Illuminate\Contracts\Foundation\Application;
use TwigBridge\Bridge as TwigBridge;
use Twig_LoaderInterface;
use View;

class CMS extends TwigBridge
{
    protected $helper;

    public function __construct(Twig_LoaderInterface $loader, $options = [], Application $app = null)
    {
        parent::__construct($loader, $options, $app);

        $this->helper = App::make('cms.helper');
    }

    /**
     * @return \CMS_Helper
     */
    public function getHelper()
    {
        return $this->helper;
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

        abort(404);
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

        abort(404);
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
        $folder = $isPage ? config('cms.path.pages') .'/' : '';

        $view = $this->normalizeName($name);

        if (View::exists($path = $folder . $view)) {
            return $path;
        }

        if (View::exists($path = $folder . $view .'/main')) {
            return $path;
        }

        return false;
    }
}