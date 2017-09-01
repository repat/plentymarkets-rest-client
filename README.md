# plentymarkets-rest-client

This is a PHP package for Plentymarkets new REST API. The API is relatively new (March 2017), so not everything might work correctly and this package might also be out of date at some point.

I'm not in anyway affiliated with Plentymarkets, nor do I get paid for this by anybody. As it says in the license, this software is 'as-is'. If you want/need more features, open a GitHub ticket or write a pull request. I'll do my best :)

You can find the Plentymarkets documentation [here](https://developers.plentymarkets.com/):

### Overview
* Functions for the 4 HTTP verbs: GET, POST, PUT, DELETE
* Automatic login and refresh if login is not valid anymore
* Simple one-time configuration with PHP array (will be saved serialzed in a file)
* Functions return an associative array

## Installation
Available via composer on [Packagist](https://packagist.org/packages/repat/plentymarkets-rest-client):

`composer require repat/plentymarkets-rest-client`

## Usage
```php
use repat\PlentymarketsRestClient\PlentymarketsRestClient;

// path to store the configuration in
$configFilePath = ".plentymarkets-rest-client.config.php";
// $config only has to be set once like this
$config = [
    "username" => "PM_USERNAME",
    "password" => "PM_PASSWORD",
    "url" => "https://www.plentymarkets-system.tld",
];

$client = new PlentymarketsRestClient($configFilePath, $config);

// After that just use it like this:
$client = new PlentymarketsRestClient($configFilePath);
```

It's possible to use the 4 HTTP verbs like this
```php
$client->get($path, $parameterArray);
$client->post($path, $parameterArray);
$client->put($path, $parameterArray);
$client->delete($path, $parameterArray);

// $parameterArray has to be a PHP array. It will be transformed into JSON automatically in case
// of POST, PUT and DELETE or into query parameters in case of GET.
// You don't _have_ to specify it, it will then just be empty
$parameterArray = [
    "createdAtFrom" => "2016-10-24T13:33:23+02:00"
];

// $path is the path you find in the Plentymarkets documentation
$path = "rest/orders/";
```

It's also possible to use the function like this. It gives you more freedom, since
you can specify the method and the $parameters given are directly given to the [guzzle
object](http://docs.guzzlephp.org/en/latest/quickstart.html).

```php
$client->singleCall("GET", $guzzleParameterArray);
```

### Errors
* If there was an error with the call (=> guzzle throws an exception) all functions will return false
* If the specified config file doesn't exist or doesn't include username/password/url, an exception will be thrown

## TODO 
* Refresh without new login but refresh-token

## Dependencies
* [https://packagist.org/packages/nesbot/carbon](nesbot/carbon) for date comparison
* [https://packagist.org/packages/guzzlehttp/guzzle](guzzlehttp/guzzle) for HTTP calls.
* [https://packagist.org/packages/danielstjules/stringy](danielstjules/stringy) for string comparisons

## License 
* see [LICENSE](https://github.com/repat/plentymarkets-rest-client/blob/master/LICENSE) file

## Changelog
* 0.1 initial release

## Contact
* Homepage: https://repat.de
* e-mail: repat@repat.de
* Twitter: [@repat123](https://twitter.com/repat123 "repat123 on twitter")

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=repat&url=https://github.com/repat/plentymarkets-rest-client&title=plentymarkets-rest-client&language=&tags=github&category=software) 

