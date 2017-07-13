<?php

namespace Vanilla\Concerns;

use Vanilla\Console\Command;
use Vanilla\Console\Commands;
use Illuminate\Support\Collection;

trait ManagesCommands {

    /**
     * The list of core commands to be enabled
     *
     * @var array
     */
    protected $coreCommands = [
        Commands\MakePostType::class,
        Commands\MakeTaxonomy::class,
        Commands\MakeEndpoint::class,
        Commands\MakeCommand::class,
        Commands\FlushRewrites::class
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
