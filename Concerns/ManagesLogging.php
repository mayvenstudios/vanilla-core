<?php
namespace Vanilla\Concerns;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

trait ManagesLogging {

    /**
     * @var Logger
     */
    protected $loggers = [];

    public function log($channel = 'vanilla')
    {
        if(!isset($this->loggers[$channel])) {
            $logger = new Logger($channel);

            $folder = $this->config('log.path', $this->path(''));
            $folder = rtrim($folder, '/');
            $file = implode(DIRECTORY_SEPARATOR, [$folder, $channel.".log"]);
            $logger->pushHandler(new StreamHandler($file, Logger::DEBUG));
            $this->loggers[$channel] = $logger;
        }
        return $this->loggers[$channel];
    }
}