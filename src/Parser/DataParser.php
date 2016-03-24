<?php


namespace Siipis\CMS\Parser;


class DataParser extends SubParser
{
    const METHOD_REGEX = "/^([a-zA-Z]+)(\[([\d]+|#[a-z]+)\])?$/";

    protected $classes;

    public function __construct(Parser $parent)
    {
        parent::__construct($parent);

        $this->init();
    }

    private function init()
    {
        $this->classes = [];

        $dir = config('cms.path.data');

        foreach (\File::files($dir) as $file) {
            $basename = basename($file, '.php');
            $className = '\App\CMS\Data\\' . $basename;

            $class = new $className();

            $this->classes[$class->getAccessor()] = $class;
        }
    }


    public function parse($source)
    {
        if ($this->parent->configHas('data')) {
            $data = $this->parent->getConfig('data');

            foreach ($data as $accessor) {
                $classAccessor = $this->getClassAccessor($accessor);
                $classMethod = $this->getClassMethod($accessor);
                $classMethodId = $this->getClassMethodId($accessor);

                if (isset($this->classes[$classAccessor])) {
                    $class = $this->classes[$classAccessor];

                    if (!is_null($classMethodId)) {
                        if (starts_with($classMethodId, '#')) {
                            $id = $this->realNumber($classMethodId);
                        } else {
                            $id = $classMethodId;
                        }

                        $dataObject = $class->$classMethod($id);

                        $this->parent->setAttribute(str_singular($classAccessor), $dataObject);
                    } else {
                        $dataObject = $class->$classMethod();

                        $this->parent->setAttribute($classAccessor, $dataObject);
                    }
                } else {
                    throw new \Exception("Unknown data accessor: $accessor.");
                }
            }
        }

        return $source;
    }

    private function getClassAccessor($accessor)
    {
        if (str_contains($accessor, ':')) {
            $parts = explode(':', $accessor);

            return $parts[0];
        } else {
            return $accessor;
        }
    }


    private function getClassMethod($accessor)
    {
        if (str_contains($accessor, ':')) {
            $parts = explode(':', $accessor);

            if (preg_match($this::METHOD_REGEX, $parts[1], $matches)) {
                return $matches[1];
            }

            throw new \BadMethodCallException("Data request call [$parts[1]] is not a valid expression.");
        } else {
            return 'all';
        }
    }

    private function getClassMethodId($accessor)
    {
        if (str_contains($accessor, ':')) {
            $parts = explode(':', $accessor);

            if (preg_match($this::METHOD_REGEX, $parts[1], $matches)) {
                if (!isset($matches[3])) {
                    return null;
                }

                return $matches[3];
            }

            throw new \BadMethodCallException("Data request call [$parts[1] is not a valid expression.");
        }

        return null;
    }

    private function realNumber($pseudoNumber)
    {
        switch ($pseudoNumber) {
            case '#auth':
                if (\Auth::check()) {
                    return \Auth::user()->id;
                } else {
                    return null;
                }
            case '#url':
                $url = explode('/', \Request::url());

                if (end($url) > 0) {
                    return intval(end($url));
                } else {
                    return end($url);
                }
            default:
                throw new \BadMethodCallException("Unknown pseudo number [$pseudoNumber].");
        }

    }

}