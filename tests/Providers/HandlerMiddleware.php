<?php

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class HandlerMiddleware
{
    private $logger;
    private $level;

    public function __construct (LoggerInterface $logger, $level = 'debug') {
        $this->logger = $logger;
        $this->level = $level;
    }

    public function __invoke (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            $this->logger->{$this->level}('Logging from HandlerMiddleware');

            return $handler($request, $options);
        };
    }
}