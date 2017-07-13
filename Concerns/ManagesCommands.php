<?php

namespace Core\Concerns;

use Core\Console\Command;
use Core\Console\Commands\MakeCommand;
use Core\Console\Commands\MakeEndpoint;
use Core\Console\Commands\MakePostType;
use Core\Console\Commands\MakeTaxonomy;
use Illuminate\Support\Collection;

trait ManagesCommands {

    /**
     * The list of core commands to be enabled
     *
     * @var array
     */
    protected $coreCommands = [
        MakePostType::class,
        MakeTaxonomy::class,
        MakeEndpoint::class,
        MakeCommand::class
    ];

    /**
     * Register custom commands
     */
    protected function registerCommands()
    {
        if (!class_exists('WP_CLI') || !$this->runningInConsole()) {
            return;
        }

        $this->commands()->each(function (Command $endpoint) {
            $endpoint->register();
        });
    }

    /**
     * Check if Application is started from cli
     *
     * @return bool
     */
    protected function runningInConsole()
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function commands()
    {
        $files = collect(glob($this->appPath('Commands/*.php')));
        $commands = $files->reduce(function(Collection $classes, $path) {
            $className = "App\\Commands\\" . rtrim(basename($path), '.php');
            if(class_exists($className)) {
                $classes->push(new $className);
            }
            return $classes;
        }, collect());

        foreach ($this->coreCommands as $command) {
            $commands->push(new $command);
        }

        return $commands;
    }
}
