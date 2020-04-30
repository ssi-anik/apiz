# APIZ

APIZ is a PHP API Client Development Kit. You can easily handle all kind of JSON api response by using this package.

## Requirements

- PHP >= 7.1

## Installations

```shell
composer require anik/apiz
```

## Configurations

There are no extra configuration for this package.

## Usage

Lets make a api service service for https://reqres.in.

Suppose you have to make several api service for your package. Your service directory is
`app/Services`. Now we are develop a service for https://reqres.in and make a class file `ReqResApiService.php`
which is extend by `\Apiz\AbstractApi` class.

```php
namespace App\Services;

use Apiz\AbstractApi;

class ReqResApiService extends AbstractApi
{
    protected function setBaseUrl() {
        return 'https://reqres.in';
    }
}
```

`AbstractApi` is a abstract class where `setBaseUrl()` is a abstract method.

To get API response from this url they've a prefix 'api' so first we set it with protected property `$prefix`

```php
namespace App\Services;

use Apiz\AbstractApi;

class ReqResApiService extends AbstractApi
{
    protected function setBaseUrl() {
        return 'https://reqres.in';
    }

    protected function setPrefix () {
        return 'api';
    }
}
```

Now we make a method for get all users info

```php
namespace App\Services;

use Apiz\AbstractApi;

class ReqResApiService extends AbstractApi
{
    protected function setBaseUrl()
    {
        return 'https://reqres.in';
    }

    protected function setPrefix () {
        return 'api';
    }

    public function allUsers()
    {
        $users = $this/*->query(['page'=>2])*/->get('/users');

        if ($users->getStatusCode() == 200) {
            return $users->parseJson();
        }

        return null;
    }
}
```

We use GuzzleHttp for this package. So you can easily use all HTTP verbs
 as a magic method. Its totally hassle free. with our all response we return three objects `response`, `request` and `contents`.
 You can access all Guzzle response method from this response. We are using magic method to access it from response.

#### Output

![Response](http://imgur.com/IgI0vKb.png?1 "Response")

## Post Request with Form Params


```php
public function createUser(array $data)
{
    $user = $this->formParams($data)
            ->post('/create');

    if ($user->getStatusCode() == 201) {
        return $user->parseJson();
    }

    return null;
}
```

## List of Parameter Options

- `formParams(array $params)`
- `headers(array $params)`
- `query(array $params)`
- `allowRedirects(array $params)`
- `auth(string $username, string $password [, array $options])`
- `body(array|string $contents)`
- `json(array $params)`
- `file(string $name, string $file_path, string $filename [, array $headers])`
- `attach (string $name, string $contents, string $filename [, array $headers])`
- `params(array $params)`

## List of HTTP verbs

- `get(string $uri)`
- `post(string $uri)`
- `put(string $uri)`
- `delete(string $uri)`
- `head(string $uri)`
- `options(string $uri)`

## Extra Methods

- `getGuzzleClient()`
## Logging

Apiz allows you to log your Request and Response. It requires to configure a few methods.

- `logger()` return `\Psr\Log\LoggerInterface` **object**. Returning null will not log any data. 
- `requestFormatter()` should return **object** satisfying `\Loguzz\Formatter\AbstractRequestFormatter` implementation. Returning null will not log request data. 
- `responseFormatter()` should return **object** satisfying `\Loguzz\Formatter\AbstractResponseFormatter` implementation. Returning null will not log response data. 
- `logRequestLength()` should return integer value. It's the length for a curl string while logging.
- `logOnlySuccessResponse()` returning `true` will only log successful response data if guzzle didn't raise any error.
- `logOnlyExceptionResponse()` returning `true`  will only log error responses like connection timeout, response timeout.
- `logLevel()` can be set to any available log level.
- `tag()` should return non-empty string which will set the log to `["tag" => "log-message"]` format.
- `forceJson()` should return boolean value which will cast the message in JSON string if `true` otherwise as array when `false`. It's internally casted to boolean value.
- `useSeparator()` should return boolean value. Used with tag like `tag.request`, `tag.success`, `tag.failure`. It's internally casted to boolean value.

## Available Request & Response Formatters.

| Formatter | Available Classes |
| ----------- | ----------- |
| Request | `\Loguzz\Formatter\RequestArrayFormatter` <br> `\Loguzz\Formatter\RequestCurlFormatter` <br> `\Loguzz\Formatter\RequestJsonFormatter`|
| Response | `\Loguzz\Formatter\ResponseArrayFormatter` <br> `\Loguzz\Formatter\ResponseJsonFormatter` |


