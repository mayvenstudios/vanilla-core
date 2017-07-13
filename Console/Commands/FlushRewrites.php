<?php

namespace Vanilla\Console\Commands;

use Vanilla\Console\Command;

class FlushRewrites extends Command {

    /**
     * Command name.
     * The command will be available as `wp {name}`
     *
     * @var string
     */
    protected $name = 'flush-rewrites';

    /**
     * Command description
     * The description to be shown in `wp help`
     *
     * @var string
     */
    protected $description = 'Flush rewrite rules';

    /**
     * Command handler
     *
     * @param $args array Command arguments
     * @param $named array Command named arguments
     *
     * @return mixed
     */
    public function handle($args, $named)
    {
        flush_rewrite_rules();
        $this->info('rewrite rules flushed');
    }
}