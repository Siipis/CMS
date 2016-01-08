<?php
namespace CMS;

use \File;

class Cache
{
    public function put($file, $contents)
    {
        if (!File::isDirectory($this->getCachePath())) {
            File::makeDirectory($this->getCachePath());
        }

        $filePath = $this->getFilePath($file);

        File::put($filePath, $contents);
    }

    public function get($file)
    {
        $filePath = $this->getFilePath($file);

        return File::get($filePath);
    }

    public function delete($file)
    {
        $filePath = $this->getFilePath($file);

        File::delete($filePath);
    }

    public function clean()
    {
        File::deleteDirectory($this->getCachePath());
    }

    public function getCachePath()
    {
        return config('cms.cms.cache');
    }

    public function getFileExtension()
    {
        return config('twigbridge.twig.extension');
    }

    public function getFilePath($file)
    {
        $file = str_replace('/', '_', $file);

        return $this->getCachePath() .'/cache_'. $file .'.' . $this->getFileExtension();
    }
}