<?php

namespace Apiz\Http;

use Apiz\Exceptions\RequirementException;
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
        $handlerStack = HandlerStack::create();

        // insert custom handlers before
        if (isset($options['handler'])) {
            // handler middleware should be the only item in $options['handler']
            // otherwise, $options['handler'][0] should contain the middleware
            // $options['handler'][1] can contain as handler name
            if (!is_array($options['handler'])) {
                throw new RequirementException('Handler must be (assoc) array');
            }
            foreach ( $options['handler'] as $key => $handler ) {
                $handlerStack->push($handler, 'custom-handler-' . $key);
            }
            unset($options['handler']);
        }

        if (isset($options['__loguzz'])) {
            $loggingOpts = $options['__loguzz'];
            $handlerStack->push(new LogMiddleware($loggingOpts['logger'], $loggingOpts), 'apiz');
            unset($options['__loguzz']);
        }

        $options['handler'] = $handlerStack;

        $opts = array_merge($default, $options);
        $this->http = new Client($opts);
    }
}
