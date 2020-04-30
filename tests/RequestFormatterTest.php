<?php

use Loguzz\Formatter\RequestArrayFormatter;
use Loguzz\Formatter\ResponseArrayFormatter;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class RequestFormatterTest extends TestCase
{
    private static $QUERIES = [
        'param-1' => 'param 1',
        'param-2' => 'param 2',
    ];
    private static $BODY = [
        'data -1' => 'data - 1',
        'data- 2' => 'data - 2',
    ];
    private static $HEADERS = [
        'custom-header-1' => 'header 1',
        'custom-header-2' => 'header 2',
    ];
    private static $DEFAULT_QUERIES = [
        'default-query-1' => 'default query value 1',
        'default-query-2' => 'default query value 2',
    ];
    private static $LOGGER = null;

    protected function tearDown () : void {
        RemoteService::$LOGGER = null;
        RemoteService::$REQUEST_FORMATTER = null;
        RemoteService::$RESPONSE_FORMATTER = null;
        RemoteService::$EXCEPTION_ONLY = false;
        RemoteService::$SUCCESS_ONLY = false;
        RemoteService::$LEVEL = 'debug';
        RemoteService::$URL = '';
        RemoteService::$PREFIX = '';
        RemoteService::$OPTIONS = [];
        RemoteService::$DEFAULT_HEADERS = [];
        RemoteService::$DEFAULT_QUERIES = [];
        self::$LOGGER = null;
    }

    protected function setUp () : void {
        RemoteService::$LOGGER = $this->getSingletonLogger();
    }

    private function getLogger () {
        return new TestLogger();
    }

    private function getSingletonLogger () {
        if (!self::$LOGGER) {
            self::$LOGGER = $this->getLogger();
        }

        return self::$LOGGER;
    }

    private function getService () {
        return new RemoteService();
    }

    private function onLogRecords ($index = null) {
        return is_null($index) ? (RemoteService::$LOGGER)->records : (RemoteService::$LOGGER)->records[$index];
    }

    private function onLogMessage ($index) {
        return $this->onLogRecords($index)['message'];
    }

    private function setUrl ($url) {
        RemoteService::$URL = $url;

        return $this;
    }

    private function setPrefix ($prefix) {
        RemoteService::$PREFIX = $prefix;

        return $this;
    }

    private function setLogLevel ($level = 'debug') {
        RemoteService::$LEVEL = $level;

        return $this;
    }

    private function setHandler () {
        RemoteService::$OPTIONS = [
            'handler' => [
                new HandlerMiddleware($this->getSingletonLogger()),
                new HandlerMiddleware($this->getSingletonLogger()),
            ],
        ];

        return $this;
    }

    private function setTimeout () {
        RemoteService::$OPTIONS = [
            'timeout'         => 0.1,
            'connect_timeout' => 0.1,
        ];

        return $this;
    }

    private function setDefaultHeaders () {
        RemoteService::$DEFAULT_HEADERS = [
            'default-header-1' => 'default-header-value-1',
            'default-header-2' => 'default-header-value-2',
        ];

        return $this;
    }

    private function setDefaultQueries () {
        RemoteService::$DEFAULT_QUERIES = self::$DEFAULT_QUERIES;

        return $this;
    }

    private function setReqFormatter ($formatter = null) {
        RemoteService::$REQUEST_FORMATTER = $formatter ? $formatter : new RequestArrayFormatter();

        return $this;
    }

    private function setResFormatter ($formatter = null) {
        RemoteService::$RESPONSE_FORMATTER = $formatter ? $formatter : new ResponseArrayFormatter();

        return $this;
    }

    private function setSuccessOnly () {
        RemoteService::$SUCCESS_ONLY = true;

        return $this;
    }

    private function setExceptionOnly () {
        RemoteService::$EXCEPTION_ONLY = true;

        return $this;
    }

    public function testNoLogWritten () {
        $service = $this->getService();
        $service->get('/');
        $this->assertEquals(0, count($this->onLogRecords()));
    }

    public function testHeadRequest () {
        $this->setReqFormatter()->getService()->head('/');
        $this->assertEquals('HEAD', $this->onLogMessage(0)['method']);
    }

    public function testGetRequest () {
        $this->setReqFormatter()->getService()->get('/');
        $this->assertEquals('GET', $this->onLogMessage(0)['method']);
    }

    public function testPostRequest () {
        $this->setReqFormatter()->getService()->post('/');
        $this->assertEquals('POST', $this->onLogMessage(0)['method']);
    }

    public function testPutRequest () {
        $this->setReqFormatter()->getService()->put('/');
        $this->assertEquals('PUT', $this->onLogMessage(0)['method']);
    }

    public function testPatchRequest () {
        $this->setReqFormatter()->getService()->patch('/');
        $this->assertEquals('PATCH', $this->onLogMessage(0)['method']);
    }

    public function testDeleteRequest () {
        $this->setReqFormatter()->getService()->delete('/');
        $this->assertEquals('DELETE', $this->onLogMessage(0)['method']);
    }

    public function testOptionsRequest () {
        $this->setReqFormatter()->getService()->options('/');
        $this->assertEquals('OPTIONS', $this->onLogMessage(0)['method']);
    }

    public function testLogLevel () {
        $this->setReqFormatter()->setLogLevel('error')->getService()->get('/');
        $this->assertEquals('error', $this->onLogRecords()[0]['level']);
    }

    public function testCustomHandlerMiddleware () {
        $this->setReqFormatter()->setHandler()->getService()->get('/');
        $this->assertEquals(3, count($this->onLogRecords()));
    }

    public function testOnlyRequestIsWritten () {
        $this->setReqFormatter()->getService()->get('/');
        $this->assertEquals(1, count($this->onLogRecords()));
        $this->assertArrayHasKey('method', $this->onLogMessage(0));
        $this->assertEquals('GET', $this->onLogMessage(0)['method']);
    }

    public function testOnlyResponseIsWritten () {
        $this->setResFormatter()->getService()->get('/');
        $this->assertArrayHasKey('protocol', $this->onLogMessage(0));
        $this->assertArrayHasKey('status_code', $this->onLogMessage(0));
        $this->assertArrayHasKey('reason_phrase', $this->onLogMessage(0));
        $this->assertEquals(1, count($this->onLogRecords()));
    }

    public function testOnlySuccessResponseWritten () {
        // will throw exception. will not write error to log will make success only true
        try {
            $this->setTimeout()->setUrl('http://no-website-should-exists-wo-tld')->setSuccessOnly()->setResFormatter()
                 ->getService()->post('/');
        } catch ( Exception $e ) {
        }
        $this->assertEquals(0, count($this->onLogRecords()));
    }

    public function testOnlyErrorResponseWritten () {
        // will throw exception. will write error to log will make error only true
        try {
            $this->setTimeout()->setUrl('http://no-website-should-exists-wo-tld')->setExceptionOnly()->setResFormatter()
                 ->getService()->post('/');
        } catch ( Exception $e ) {
        }
        $this->assertEquals(1, count($this->onLogRecords()));
    }

    public function testErrorResponseWritten () {
        try {
            $this->setTimeout()->setUrl('http://no-website-should-exists-wo-tld')->setResFormatter()->getService()
                 ->post('/');
        } catch ( Exception $E ) {
        }
        $this->assertStringContainsString("url", $this->onLogMessage(0));
        $this->assertStringContainsString("content_type", $this->onLogMessage(0));
        $this->assertStringContainsString("http_code", $this->onLogMessage(0));
    }

    public function testSetPrefixTest () {
        $this->setReqFormatter()->setPrefix('prefix-test')->getService()->get('get');
        $this->assertStringContainsString('/prefix-test/', $this->onLogMessage(0)['url']);
    }

    public function testAuthentication () {
        $this->setReqFormatter()->getService()->auth('user', 'password')->post('post');
        $this->assertArrayHasKey('Authorization', $this->onLogMessage(0)['headers']);
        $hash = base64_encode('user:password');
        $this->assertStringContainsString($hash, $this->onLogMessage(0)['headers']['Authorization']);
    }

    public function testGetQueryParams () {
        $this->setReqFormatter()->getService()->query(self::$QUERIES)->get('/');
        $params = implode('&', array_map(function ($key, $value) {
            return urlencode($key) . '=' . rawurlencode($value);
        }, array_keys(self::$QUERIES), array_values(self::$QUERIES)));
        $this->assertStringContainsString($params, $this->onLogMessage(0)['url']);
    }

    public function testDefaultQueriesInRequest () {
        $this->setDefaultQueries()->setReqFormatter()->getService()->query(self::$QUERIES)->post('/');
        $params = implode('&', array_map(function ($key, $value) {
            return urlencode($key) . '=' . rawurlencode($value);
        }, array_keys(self::$DEFAULT_QUERIES), array_values(self::$DEFAULT_QUERIES)));
        $this->assertStringContainsString($params, $this->onLogMessage(0)['url']);
    }

    public function testSkipDefaultQueriesInRequest () {
        $this->setDefaultQueries()->setReqFormatter()->getService()->query(self::$QUERIES)->skipDefaultQueries()
             ->post('/');
        $params = implode('&', array_map(function ($key, $value) {
            return urlencode($key) . '=' . rawurlencode($value);
        }, array_keys(self::$DEFAULT_QUERIES), array_values(self::$DEFAULT_QUERIES)));
        $this->assertStringNotContainsString($params, $this->onLogMessage(0)['url']);
    }

    public function testHeadersInRequest () {
        $this->setReqFormatter()->getService()->headers(self::$HEADERS)->post('/');
        $this->assertArrayHasKey('custom-header-1', $this->onLogMessage(0)['headers']);
        $this->assertArrayHasKey('custom-header-2', $this->onLogMessage(0)['headers']);
    }

    public function testDefaultHeadersInRequest () {
        $this->setDefaultHeaders()->setReqFormatter()->getService()->post('/');
        $this->assertArrayHasKey('default-header-1', $this->onLogMessage(0)['headers']);
        $this->assertArrayHasKey('default-header-1', $this->onLogMessage(0)['headers']);
    }

    public function testSkipDefaultHeadersInRequest () {
        $this->setDefaultHeaders()->setReqFormatter()->getService()->skipDefaultHeaders()->post('/');
        $this->assertArrayNotHasKey('default-header-1', $this->onLogMessage(0)['headers']);
        $this->assertArrayNotHasKey('default-header-1', $this->onLogMessage(0)['headers']);
    }

    public function testPostBodyRequest () {
        $this->setReqFormatter()->getService()->body(self::$BODY)->post('/');
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertEquals(json_encode(self::$BODY), $this->onLogMessage(0)['data']);
    }

    public function testPostFormBodyRequest () {
        try {
            $this->setReqFormatter()->setResFormatter()->getService()->formParams(self::$BODY)->patch('patch');
        } catch ( Exception $e ) {
        }
        $this->assertEquals('PATCH', $this->onLogMessage(0)['method']);
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertEquals('application/x-www-form-urlencoded', $this->onLogMessage(0)['headers']['Content-Type']);
        $this->assertEquals(http_build_query(self::$BODY, '', '&'), $this->onLogMessage(0)['data']);
    }

    public function testFileContent () {
        try {
            $this->setReqFormatter()->getService()
                 ->file('file', __DIR__ . '/profile-picture.png', 'profile-picture.png')->post('/post');
        } catch ( Exception $e ) {
        }
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertStringContainsString('multipart/form-data', $this->onLogMessage(0)['headers']['Content-Type']);
    }

    public function testRawFileContent () {
        try {
            $this->setReqFormatter()->getService()
                 ->attach('file', fopen(__DIR__ . '/profile-picture.png', 'r'), 'profile-picture.png')->post('/post');
        } catch ( Exception $e ) {
        }
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertStringContainsString('multipart/form-data', $this->onLogMessage(0)['headers']['Content-Type']);
    }

    public function testFormData () {
        try {
            $this->setReqFormatter()->getService()->formData([
                'file'        => fopen(__DIR__ . '/profile-picture.png', 'r'),
                'form-data-1' => 'form-value-1',
            ])->post('/post');
        } catch ( Exception $e ) {
        }
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertStringContainsString('multipart/form-data', $this->onLogMessage(0)['headers']['Content-Type']);
        $this->assertStringContainsString('Content-Disposition: form-data; name="form-data-1"',
            $this->onLogMessage(0)['data']);
    }

    public function testParamData () {
        try {
            $this->setReqFormatter()->getService()->params([
                'multipart' => [
                    [
                        'name'     => 'file-data',
                        'contents' => fopen(__DIR__ . '/profile-picture.png', 'r'),
                        'headers'  => [ 'X-Custom-Header' => 'XCH-value-1' ],
                    ],
                    [
                        'name'     => 'form-data-1',
                        'contents' => 'form-value-1',
                    ],
                ],
            ])->post('/post');
        } catch ( Exception $e ) {
        }
        $this->assertArrayHasKey('Content-Type', $this->onLogMessage(0)['headers']);
        $this->assertStringContainsString('multipart/form-data', $this->onLogMessage(0)['headers']['Content-Type']);
        $this->assertStringContainsString('Content-Disposition: form-data; name="file-data";',
            $this->onLogMessage(0)['data']);
        $this->assertStringContainsString('Content-Disposition: form-data; name="form-data-1"',
            $this->onLogMessage(0)['data']);
    }

    public function testGuzzleClient () {
        $this->assertInstanceOf(GuzzleHttp\Client::class, $this->getService()->getGuzzleClient());
    }

    public function testGetContents () {
        $r = $this->getService()->get('/');
        $this->assertIsString($r->getContents());
    }

    public function testGetJson () {
        $r = $this->getService()->get('/');
        $this->assertIsArray($r->parseJson(true));
        $this->assertIsObject($r->parseJson());
    }
}