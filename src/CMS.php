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

    public function getHelper()
    {
        return $this->helper;
    }

    public function route($route, $isPage = true)
    {
        if ($view = $this->getViewName($route, $isPage))
        {
            return $this->render($view);
        }

        abort(404);
        return false;
    }

    public function view($view, $isPage = true)
    {
        return $this->route($view, $isPage);
    }

    protected function getViewName($name, $isPage = true)
    {
        $folder = $isPage ? config('cms.path.pages') .'/' : '';
        $view = $this->normalizeName($name);

        if (View::exists($path = $folder . $view)) {
            return $path;
        }

        if (View::exists($path .= '/main')) {
            return $path;
        }

        return false;
    }
}