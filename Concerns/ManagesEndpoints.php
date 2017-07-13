<?php

namespace Vanilla\Concerns;

use Vanilla\Endpoint;
use Illuminate\Support\Collection;

trait ManagesEndpoints {

    /**
     * Custom endpoints to be registered
     *
     * @var array
     */
    protected $endpoints = [];

    /**
     * Register custom endpoints
     */
    protected function registerEndpoints()
    {
        $this->endpoints()->each(function (Endpoint $endpoint) {
            $endpoint->register();
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function endpoints()
    {
        $files = collect(glob($this->appPath('Endpoints/*.php')));
        return $files->reduce(function(Collection $classes, $path) {
            $className = "App\\Endpoints\\" . rtrim(basename($path), '.php');
            if(class_exists($className)) {
                $classes->push(new $className);
            }
            return $classes;
        }, collect());
    }
}