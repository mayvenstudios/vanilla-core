<?php

namespace Vanilla\Concerns;

trait ManagesPaths {
    
    /** @var string */
    protected $rootPath = '';

    /**
     * Set root path
     *
     * @param $path
     */
    public function setPath($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->rootPath = $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function configPath($path = '')
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->path("config" . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function path($path = '')
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->rootPath . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function extensionsPath($path = '')
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->path(join(DIRECTORY_SEPARATOR, ['extensions', $path]));
    }

    public function viewsPath($path = '')
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->path(join(DIRECTORY_SEPARATOR, ['views', $path]));
    }

    public function compiledPath($path = '')
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return join(DIRECTORY_SEPARATOR, [uploads_path('view'), $path]);
    }

    public function appPath($path)
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->path(join(DIRECTORY_SEPARATOR, ['app', $path]));
    }

    public function assetsPath($path)
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        return $this->path(join(DIRECTORY_SEPARATOR, ['assets', $path]));
    }
}