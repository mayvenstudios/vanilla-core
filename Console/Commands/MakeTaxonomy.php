<?php

namespace Vanilla\Console\Commands;

use Vanilla\Console\Generator;

class MakeTaxonomy extends Generator {

    /**
     * Command name.
     * The command will be available as `wp {name}`
     *
     * @var string
     */
    protected $name = 'make:taxonomy';

    /**
     * Command description
     * The description to be shown in `wp help`
     *
     * @var string
     */
    protected $description = 'Create new taxonomy';

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
        $this->setPath('Taxonomies')->setStubName('Taxonomy')->exec($args);
    }
}