<?php


namespace CMS\Parser;


class DataParser extends SubParser
{
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

                if (isset($this->classes[$classAccessor])) {
                    $class = $this->classes[$classAccessor];

                    $dataObject = $class->$classMethod();

                    $this->parent->setAttribute($classAccessor, $dataObject);

                } else {
                    throw new \Exception('Unknown data accessor: ' . $accessor);
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

            return $parts[1];
        } else {
            return 'all';
        }
    }

}