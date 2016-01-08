<?php
namespace CMS\Template;

use Carbon;
use CMS\Facade\Cache as Cache;
use File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Twig;
use YAML;

class Template
{
    /**
     * Path the templates are stored in.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Template filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Template file contents.
     *
     * @var string
     */
    protected $file;

    /**
     * Timestamp
     *
     * @var \Carbon\Carbon
     */
    protected $readAt;

    /**
     * Template config.
     *
     * @var array
     */
    protected $config;

    /**
     * Template content.
     *
     * @var string
     */
    protected $content;

    protected $cache;

    public function __construct($path, $folder = 'pages')
    {
        $this->basePath = config("cms.path.$folder");
        $this->filename = $path;

        $this->parse();
    }

    public function parse()
    {
        $file = $this->readFile();

        $this->split($file);
    }

    protected function readFile()
    {
        foreach(config('view.paths') as $viewPath)
        {
            $filePath = $viewPath .'/'. $this->basePath .'/'. $this->filename . '.twig';

            if (File::exists($filePath)) {
                $this->file = File::get($filePath);
                $this->readAt = Carbon::now();
                break;
            }
        }

        if (is_null($this->file)) {
            throw new FileNotFoundException();
        }

        return $this->file;
    }

    public function split($file)
    {
        $parts = explode('===', $file);

        array_map('trim', $parts);

        switch (count($parts)) {
            case 1:
                $this->config = [];
                $this->content = $parts[0];
                break;
            case 2:
                $this->config = YAML::parse($parts[0]);
                $this->content = $parts[1];
                break;
            default:
                throw new \Twig_Error_Syntax("Invalid template format.");
                break;
        }

        return [
            'config' => $this->config,
            'content' => $this->content,
        ];
    }

    public function render()
    {
        Cache::put($this->filename, $this->content);

        $filePath = Cache::getFilePath($this->filename);
        $template = Twig::render($filePath);

        Cache::delete($this->filename);

        if (isset($this->config['title']))
        {
            Twig::addGlobal('cms.title', $this->config['title']);
        }

        if (isset($this->config['layout']))
        {
            Twig::addGlobal('cms.page', $template);
            return Twig::render(config('cms.path.layouts') .'/'. $this->config['layout']);
        }
        else
        {
            return $template;
        }
    }
}