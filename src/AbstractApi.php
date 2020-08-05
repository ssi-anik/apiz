<?php

namespace Apiz;

use Apiz\Exceptions\HttpExceptionReceiver;
use Apiz\Exceptions\RequirementException;
use Apiz\Http\Request;
use Apiz\Http\Response;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Loguzz\Formatter\AbstractRequestFormatter;
use Loguzz\Formatter\AbstractResponseFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @method Response get(string $uri)
 * @method Response post(string $uri)
 * @method Response put(string $uri)
 * @method Response delete(string $uri)
 * @method Response head(string $uri)
 * @method Response options(string $uri)
 * @method Response patch(string $uri)
 */
abstract class AbstractApi
{
    /**
     * list of available http exceptions
     *
     * @var array
     */
    protected $httpExceptions = [];

    /**
     * skip exception when its value true
     *
     * @var bool
     */
    protected $skipHttpException = false;

    /**
     * Options for guzzle clients
     *
     * @var array
     */
    protected $options = [];

    /**
     * guzzle base URL
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * URL prefix
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * this variable contains request details
     *
     * @var array
     */
    protected $request = [];

    /**
     * Default headers options for request
     *
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * Default Query options for request
     *
     * @var array
     */
    protected $defaultQueries = [];

    /**
     * when need to skip default header make it true
     *
     * @var bool
     */
    protected $skipDefaultHeader = false;

    /**
     * when need to skip default query make it true
     *
     * @var bool
     */
    protected $skipDefaultQueries = false;

    /**
     * Guzzle http object
     *
     * @var Request
     */
    protected $client;

    /**
     * Request parameters
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * All supported HTTP verbs
     *
     * @var array
     */
    private $requestMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'PATCH',
    ];

    public function __construct () {
        $this->baseUrl = $this->setBaseUrl();

        $this->defaultHeaders = $this->setDefaultHeaders();
        $this->defaultQueries = $this->setDefaultQueries();

        /* Prepare Loguzz Options */
        if ($logger = $this->logger()) {
            if (!$logger instanceof LoggerInterface) {
                throw new RequirementException('Logger should satisfy Psr\Log\LoggerInterface interface');
            }

            $options = [
                'logger'          => $logger,
                'log_request'     => false,
                'log_response'    => false,
                'success_only'    => $this->logOnlySuccessResponse(),
                'exceptions_only' => $this->logOnlyExceptionResponse(),
                'log_level'       => $this->logLevel(),
                'length'          => $this->logRequestLength(),
            ];

            if ($tag = $this->tag()) {
                $options['tag'] = $tag;
                $options['force_json'] = $this->forceJson();
                $options['separate'] = $this->useSeparator();
            }

            if ($requestFormatter = $this->requestFormatter()) {
                if (!$requestFormatter instanceof AbstractRequestFormatter) {
                    throw new RequirementException('RequestFormatter should be an instanceof AbstractRequestFormatter');
                }
                $options['log_request'] = true;
                $options['request_formatter'] = $requestFormatter;
            }

            if ($responseFormatter = $this->responseFormatter()) {
                if (!$responseFormatter instanceof AbstractResponseFormatter) {
                    throw new RequirementException('ResponseFormatter should be an instanceof AbstractResponseFormatter');
                }
                $options['log_response'] = true;
                $options['response_formatter'] = $responseFormatter;
            }

            $this->options['__loguzz'] = $options;
        }

        $this->client = new Request($this->baseUrl, $this->options);
    }

    /**
     * set base URL for guzzle client
     *
     * @return string
     */
    abstract protected function setBaseUrl ();

    /**
     * set url prefix from code
     *
     * @return null|string
     */
    protected function setPrefix () {
        return null;
    }

    /**
     * set default headers that will automatically bind with every request headers
     *
     * @return array
     */
    protected function setDefaultHeaders () {
        return [];
    }

    /**
     * set default queries that will automatically bind with every request headers
     *
     * @return array
     */
    protected function setDefaultQueries () {
        return [];
    }

    /**
     * @return null|\Psr\Log\LoggerInterface
     */
    protected function logger () {
        return null;
    }

    /**
     * @return null|\Loguzz\Formatter\AbstractRequestFormatter
     */
    protected function requestFormatter () {
        return null;
    }

    /**
     * @return null|\Loguzz\Formatter\AbstractResponseFormatter
     */
    protected function responseFormatter () {
        return null;
    }

    /**
     * @return int
     */
    protected function logRequestLength () {
        return 100;
    }

    /**
     * @return bool
     */
    protected function logOnlySuccessResponse () {
        return false;
    }

    /**
     * @return bool
     */
    protected function logOnlyExceptionResponse () {
        return false;
    }

    /**
     * @return string
     */
    protected function logLevel () {
        return 'info';
    }

    /**
     * @return string
     */
    protected function tag () {
        return '';
    }

    /**
     * @return bool
     */
    protected function forceJson () {
        return true;
    }

    /**
     * @return bool
     */
    protected function useSeparator () {
        return false;
    }

    /**
     * set form parameters or form data for POST, PUT and PATCH request
     *
     * @param array $params
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function formParams ($params = []) {
        if (is_array($params)) {
            $this->parameters['form_params'] = $params;

            return $this;
        }

        return false;
    }

    /**
     * set request headers
     *
     * @param array $params
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function headers ($params = []) {
        if (is_array($params)) {
            $this->parameters['headers'] = $params;

            return $this;
        }

        return false;
    }

    /**
     * @return \Apiz\AbstractApi
     */
    protected function skipDefaultHeaders () {
        $this->skipDefaultHeader = true;

        return $this;
    }

    /**
     * @return \Apiz\AbstractApi
     */
    protected function skipDefaultQueries () {
        $this->skipDefaultQueries = true;

        return $this;
    }

    /**
     * set query parameters
     *
     * @param array $params
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function query ($params = []) {
        if (is_array($params)) {
            $this->parameters['query'] = $params;

            return $this;
        }

        return false;
    }

    /**
     * Add allow redirects param
     *
     * @param array $params
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function allowRedirects ($params = []) {
        if (is_array($params)) {
            $this->parameters['allow_redirects'] = $params;

            return $this;
        }

        return false;
    }

    /**
     * Set basic auth options
     *
     * @param       $username
     * @param       $password
     * @param array $opts
     *
     * @return \Apiz\AbstractApi
     */
    protected function auth ($username, $password, $opts = []) {
        $params = [ $username, $password ];

        if (is_array($opts)) {
            $params = array_merge($params, $opts);
        }

        $this->parameters['auth'] = $params;

        return $this;
    }

    /**
     * Set request body
     *
     * @param string|blob|array
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function body ($contents) {
        if (is_array($contents)) {
            $this->headers([
                'Content-Type' => 'application/json',
            ]);

            $contents = json_encode($contents);
        }
        $this->parameters['body'] = $contents;

        return $this;
    }

    /**
     * Set request param as JSON
     *
     * @param array $params
     *
     * @return \Apiz\AbstractApi|bool
     */
    protected function json ($params = []) {
        if (is_array($params)) {
            $this->parameters['json'] = $params;

            return $this;
        }

        return false;
    }

    /**
     * Send file to the request
     *
     * @param       $name
     * @param       $file
     * @param       $filename
     * @param array $headers
     *
     * @return \Apiz\AbstractApi
     */
    protected function file ($name, $file, $filename, $headers = []) {
        $params = [];

        if (file_exists($file)) {
            $contents = fopen($file, 'r');

            $params = [
                'name'     => $name,
                'contents' => $contents,
                'filename' => $filename,
                'headers'  => $headers,
            ];
        }

        $this->parameters['multipart'][] = $params;

        return $this;
    }

    /**
     * Attach a raw content with request
     *
     * @param       $name
     * @param       $contents
     * @param       $filename
     * @param array $headers
     *
     * @return \Apiz\AbstractApi
     */
    protected function attach ($name, $contents, $filename, $headers = []) {
        $params = [
            'name'     => $name,
            'contents' => $contents,
            'filename' => $filename,
            'headers'  => $headers,
        ];

        $this->parameters['multipart'][] = $params;

        return $this;
    }

    /**
     * Attach form value with multipart
     *
     * @param array $data
     *
     * @return \Apiz\AbstractApi
     */
    protected function formData ($data = []) {
        foreach ( $data as $key => $value ) {
            $params = [
                'name'     => $key,
                'contents' => $value,
            ];

            $this->parameters['multipart'][] = $params;
        }

        return $this;
    }

    /**
     * Set all parameters from this single options
     *
     * @param array $options
     *
     * @return \Apiz\AbstractApi
     */
    protected function params ($options = []) {
        $this->parameters = $options;

        return $this;
    }

    /**
     * skip default http exception from request
     *
     * @param array $exceptions
     *
     * @return \Apiz\AbstractApi
     */
    protected function skipHttpExceptions (array $exceptions = []) {
        if (count($exceptions) > 0) {
            foreach ( $exceptions as $code ) {
                unset($this->httpExceptions[$code]);
            }

            return $this;
        }

        $this->skipHttpException = true;

        return $this;
    }

    /**
     * push new http exceptions to current request
     *
     * @param array $exceptions
     *
     * @return \Apiz\AbstractApi
     */
    protected function pushHttpExceptions (array $exceptions = []) {
        foreach ( $exceptions as $code => $exception ) {
            $this->httpExceptions[$code] = $exception;
        }

        return $this;
    }

    /**
     * Prepare the Request
     *
     * @param string $method
     * @param string $uri
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function prepareRequest ($method, $uri) {
        if (!is_null($this->setPrefix())) {
            $this->prefix = $this->setPrefix();
        }

        if (!empty($this->prefix)) {
            $this->prefix = trim($this->prefix, '/') . '/';
        }
        $uri = $this->prefix . trim($uri, '/');

        $this->mergeDefaultHeaders();
        $this->mergeDefaultQueries();

        $this->request = [
            'url'        => trim($this->baseUrl, '/') . '/' . $uri,
            'method'     => $method,
            'parameters' => $this->parameters,
        ];

        $request = new Psr7Request($method, $uri);

        return $request;
    }

    /**
     * Get Guzzle's async request
     * In case someone wants to use async requests
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param array                              $params
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function asyncRequest (RequestInterface $request, array $params = []) {
        return $this->client->http->sendAsync($request, array_merge($this->parameters, $params));
    }

    /**
     * Make all request from here
     *
     * @param string $method
     * @param string $uri
     *
     * @return Response
     * @throws \Apiz\Exceptions\NoResponseException
     */
    protected function makeMethodRequest ($method, $uri) {
        $request = $this->prepareRequest($method, $uri);

        try {
            $response = $this->asyncRequest($request)->wait();
        } catch ( RequestException $e ) {
            $response = $e->getResponse();
        } catch ( ClientException $e ) {
            $response = $e->getResponse();
        } catch ( BadResponseException $e ) {
            $response = $e->getResponse();
        } catch ( ServerException $e ) {
            $response = $e->getResponse();
        }

        if (!$this->skipHttpException) {
            if ($response instanceof GuzzleResponse) {
                new HttpExceptionReceiver($response, $this->httpExceptions);
            }
        }

        $resp = new Response($response, $request);
        $this->resetObjects();

        return $resp;
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl () {
        return $this->baseUrl;
    }

    /**
     * Get Guzzle http client object
     *
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleClient () {
        return $this->client->http;
    }

    /**
     * Reset this class objects
     */
    protected function resetObjects () {
        $clearGarbage = [
            'skipDefaultHeader' => false,
            'options'           => [],
            'request'           => [],
            'parameters'        => [],
            'skipHttpException' => false,
        ];

        foreach ( $clearGarbage as $key => $value ) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    protected function mergeDefaultHeaders () {
        if (!$this->skipDefaultHeader) {
            if (isset($this->parameters['headers'])) {
                $this->parameters['headers'] = array_merge($this->defaultHeaders, $this->parameters['headers']);
            } else {
                $this->parameters['headers'] = $this->defaultHeaders;
            }

            if (count($this->parameters['headers']) < 1) {
                unset($this->parameters['headers']);
            }
        }
    }

    protected function mergeDefaultQueries () {
        if (!$this->skipDefaultQueries) {
            if (isset($this->parameters['query'])) {
                $this->parameters['query'] = array_merge($this->defaultQueries, $this->parameters['query']);
            } else {
                $this->parameters['query'] = $this->defaultQueries;
            }

            if (count($this->parameters['query']) < 1) {
                unset($this->parameters['query']);
            }
        }
    }

    /**
     * @param $func
     * @param $params
     *
     * @return Response
     * @throws \Apiz\Exceptions\RequirementException
     */
    public function __call ($func, $params) {
        $method = strtoupper($func);
        if (in_array($method, $this->requestMethods)) {
            $parameters[] = $method;
            $parameters[] = $params[0];
            $content = call_user_func_array([ $this, 'makeMethodRequest' ], $parameters);

            return $content;
        }

        throw new RequirementException('Invalid method ' . $method);
    }
}
