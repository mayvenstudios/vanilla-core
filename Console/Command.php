<?php

namespace Vanilla\Console;

use WP_CLI;

/**
 * Class Command
 * Wrapper on top of the wp-cli tool
 *
 * @see http://wp-cli.org/
 *
 * @package Vanilla
 */
abstract class Command {

    /**
     * Command name.
     * The command will be available as `wp {name}`
     *
     * @var string
     */
    protected $name;

    /**
     * Command description
     * The description to be shown in `wp help`
     *
     * @var string
     */
    protected $description = '';

    /**
     * Command handler
     *
     * @param $args array Command arguments
     * @param $named array Command named arguments
     */
    abstract public function handle($args, $named);

    /**
     * Register the command
     */
    public function register()
    {
        WP_CLI::add_command($this->name, [$this, 'handler'], ['shortdesc' => $this->description]);
    }

    /**
     * @param $args
     * @param $named
     */
    public function handler($args, $named)
    {
        try {
            $this->handle($args, $named);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Log info into the console
     *
     * @param string $string
     */
    protected function info($string = '')
    {
        WP_CLI::log($string);
    }

    /**
     * Log error into the console
     *
     * @param $string
     */
    protected function error($string)
    {
        WP_CLI::error($string);
    }
}
