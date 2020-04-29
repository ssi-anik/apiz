<?php

namespace Apiz\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;

class Request
{
    /**
     * Guzzle http client object
     *
     * @var Client
     */
    public $http;

    public function __construct ($base_url, $options = []) {
        $default = [ 'base_uri' => $base_url, 'timeout' => 30.0 ];

        if (isset($options['__loguzz'])) {
            $loggingOpts = $options['__loguzz'];

            $handlerStack = HandlerStack::create();
            $handlerStack->push(new LogMiddleware($loggingOpts['logger'], $loggingOpts), 'apiz');
            $default['handler'] = $handlerStack;
            unset($options['__loguzz']);
        }

        $opts = array_merge($default, $options);
        $this->http = new Client($opts);
    }
}
