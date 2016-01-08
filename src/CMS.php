<?php
namespace CMS;

use CMS\Template\Template;
use View;

class CMS
{
    public function route($route)
    {
        if ($view = $this->getView($route))
        {
            $template = new Template($view);

            return $template->render();
        }
        else
        {
            abort(404);
            return false;
        }
    }

    public function render($view)
    {
        return $this->route($view);
    }

    private function getView($route)
    {
        $prefix = config('cms.path.pages');
        $view = $this->parseNotation($route);

        if (View::exists($prefix .'/'. $view)) {
            return $view;
        }

        if (View::exists($prefix .'/'. $view .'/main')) {
            return $view .'/main';
        }

        return false;
    }

    /**
     * Parses the dot notation
     *
     * @param $notation
     * @return null|string
     */
    private function parseNotation($notation)
    {
        $parsed = null;
        $parts = explode('.', $notation);

        foreach($parts as $i => $part) {
            if ($part == config('twigbridge.twig.extension')) {
                $parsed .= ".$part";
                continue;
            }

            if ($i > 0) {
                $parsed .= "/$part";
                continue;
            }

            $parsed = $part;
        }

        return $parsed;
    }
}