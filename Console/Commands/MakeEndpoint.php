<?php

namespace Core\Console\Commands;

use Core\Console\Generator;

class MakeEndpoint extends Generator {

    /**
     * Command name.
     * The command will be available as `wp {name}`
     *
     * @var string
     */
    protected $name = 'make:endpoint';

    /**
     * Command description
     * The description to be shown in `wp help`
     *
     * @var string
     */
    protected $description = 'Create new endpoint';

    /**
     * Command handler
     *
     * @param $args array Command arguments
     * @param $named array Command named arguments
     *
     * @return mixed|void
     * @throws \Exception
     */
    public function handle($args, $named)
    {
        $this->setPath('Endpoints')->setStubName('Endpoint')->exec($args);
    }
}