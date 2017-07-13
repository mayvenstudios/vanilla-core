<?php

namespace Core\Concerns;

trait Configurable {
    /**
     * @var array loaded configuration
     */
    protected $configuration = [];

    /**
     * Fetch particular configuration setting
     *
     * @param $key string
     * @param $default mixed Default value
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return array_get($this->configuration, $key, $default);
    }

    /**
     * Load the configuration to be available through $this->config('name')
     *
     * @see $this->config()
     */
    protected function loadConfiguration()
    {
        $this->configuration = require_once $this->configPath('app.php');
        $files = glob($this->configPath('*'));
        foreach ($files as $path) {
            list($name) = explode('.', basename($path));
            if ($name !== 'app') {
                $this->configuration[$name] = require_once $path;
            }
        }
    }
}