<?php

use Apiz\AbstractApi;

class RemoteService extends AbstractApi
{
    static $LOGGER = null;
    static $REQUEST_FORMATTER = null;
    static $RESPONSE_FORMATTER = null;
    static $EXCEPTION_ONLY = false;
    static $SUCCESS_ONLY = false;
    static $LEVEL = 'debug';
    static $URL = '';
    static $PREFIX = '';
    static $TAG = '';
    static $FORCE_JSON = true;
    static $OPTIONS = [];
    static $DEFAULT_HEADERS = [];
    static $DEFAULT_QUERIES = [];

    public function __construct () {
        $this->options = static::$OPTIONS;
        // $this->options = [ 'handler' => new HandlerMiddleware() ];
        // $this->options = [ 'handler' => [new HandlerMiddleware()] ];
        // $this->options = [ 'handler' => [new HandlerMiddleware(),new HandlerMiddleware(),new HandlerMiddleware(),] ];
        parent::__construct();
    }

    protected function logger () {
        return static::$LOGGER;
    }

    protected function requestFormatter () {
        return static::$REQUEST_FORMATTER;
    }

    protected function responseFormatter () {
        return static::$RESPONSE_FORMATTER;
    }

    protected function logRequestLength () {
        return 1000;
    }

    protected function logOnlySuccessResponse () {
        return static::$SUCCESS_ONLY;
    }

    protected function logOnlyExceptionResponse () {
        return static::$EXCEPTION_ONLY;
    }

    protected function logLevel () {
        return static::$LEVEL;
    }

    protected function setPrefix () {
        return self::$PREFIX;
    }

    protected function setBaseUrl () {
        return static::$URL ? static::$URL : $_ENV['SANDBOX_URL'];
    }

    public function setDefaultHeaders () {
        return static::$DEFAULT_HEADERS;
    }

    public function setDefaultQueries () {
        return static::$DEFAULT_QUERIES;
    }

    public function tag () : string {
        return static::$TAG;
    }

    public function forceJson () {
        return static::$FORCE_JSON;
    }

    public function __call ($func, $params) {
        if (method_exists($this, $func)) {
            return $this->{$func}(...$params);
        }

        return parent::__call($func, $params);
    }
}