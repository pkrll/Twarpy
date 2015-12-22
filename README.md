## Twarpy [![Build Status](https://travis-ci.org/pkrll/Twarpy.svg?branch=master)](https://travis-ci.org/pkrll/Twarpy) [![Latest Stable Version](https://poser.pugx.org/saturn/twarpy/v/stable)](https://packagist.org/packages/saturn/twarpy) [![License](https://poser.pugx.org/saturn/twarpy/license)](https://packagist.org/packages/saturn/twarpy)
A simple and small PHP library for accessing Twitter's REST API (v1.1) using 3-Legged OAuth or Application-only tokens.
#### Requirements
* PHP >= version 5.3.
* Curl extension.

#### Installation
There are two ways to install Twarpy. If you prefer using composer, just run the following command in the terminal at the root directory of your project:
```bash
$ composer require saturn/twarpy
```
You can also include it as a dependency in your composer.json file and then run ``composer update``:
```
{
    "require": {
        "saturn/twarpy": "^2.0"
    }
}
```
You can also download the source directly and include it in your project directory manually, or clone this repository:
```bash
$ git clone git://github.com/pkrll/Twarpy.git
```
If you've installed Twarpy with Composer, you can use the autoloader:
```php
use Twarpy\Twarpy;
include __DIR__ . "/vendor/autoload.php";
```
#### Usage
* The first step is to register your application with [Twitter](https://apps.twitter.com).
* Copy the consumer key and consumer secret (never share these keys with anyone).

##### Using 3-Legged OAuth
* Using 3-Legged OAUth flow allows the app to read or post to Twitter on the users behalf. But the user must authenticate each requests.
* To initialize Twarpy with this auth method, you need the previously collected consumer key and secret tokens along with an oauth token and oauth secret. If a user has not authenticated the app, they will be redirected to Twitter.com. Upon authorization, the application will collect the newly granted access token (remember to save the tokens once you've retrieved them by using the method ``getAccessToken()``, so that the user does not need to authenticate each request manually).
* Create a new Twarpy object, with an array containing the configuration and a constant representing the auth method:
```php
$config = array(
  "consumer_key"    => "YOURCONSUMERKEY",
  "consumer_secret" => "YOURCONSUMERSECRET"
);
$Twarpy = new Twarpy($config, THREE_LEGGED);
// Save the oauth token and oauth token secret The next time you
// run Twarpy for that user you can include the tokens in the config array.
$tokens = $Twarpy->getAccessToken(); // returns array("access_token" => ???, "token_secret" => ???)
```
* Next time you want to make an API call on that users behalf, you can include the retrieved tokens in the ``$config``-array:
```php
$config = array(
  "consumer_key"    => "YOURCONSUMERKEY",
  "consumer_secret" => "YOURCONSUMERSECRET",
  "access_token"    => "YOURACCESSTOKEN",
  "token_secret"    => "YOURSECRETTOKEN"
);
$Twarpy = new Twarpy($config, THREE_LEGGED);
```
##### Using Application-only auth
* The app-only auth method is similarly to the 3-Legged method, but does not require the user to authenticate the application. This auth method does not allow for requests that require user context, like creating or deleting tweets.
* To initialize Twarpy with this auth method, you need the previously collected consumer key and secret tokens.
* Create the Twarpy object and use the constant ``APP_ONLY`` as the second parameter:
```php
$config = array(
  "consumer_key"    => "YOURCONSUMERKEY",
  "consumer_secret" => "YOURCONSUMERSECRET"
);
$Twarpy = new Twarpy($config, APP_ONLY);

$tokens = $Twarpy->getAccessToken();
// returns array("access_token" => ???)
```
* Save the access token for faster requests and include in the ``$config``-array with the key ``access_token`` (please note, this method does not require a secret token).
```php
$config = array(
  "consumer_key"    => "YOURCONSUMERKEY",
  "consumer_secret" => "YOURCONSUMERSECRET",
  "access_token"    => "YOURACCESSTOKEN"
);
$Twarpy = new Twarpy($config, APP_ONLY);
```
##### Making requests
Making requests to the Twitter API is supereasy. All you need is the http method (GET/POST), the api path and, if needed, a parameters array. Consult the Twitter API documentation for available requests.
```php
$params = array("screen_name" => "twitter");
$data = $Twarpy->request('statuses/user_timeline', 'GET', $params);
```
###### Example request
```php
use Twarpy\Twarpy;
include __DIR__ . "/vendor/autoload.php";

$config = array(
    'consumer_key'      => "YOURCONSUMERKEY",
    'consumer_secret'   => "YOURCONSUMERSECRET",
    'access_token'      => 'YOURACCESSTOKEN',
    'token_secret'      => 'YOURSECRETTOKEN'
);

try {
    $twarpy = new Twarpy($config, THREE_LEGGED);
    $tweet  = array("status" => "Testing Twarpy! #Twarpy");
    $data   = $twarpy->request('statuses/update', 'POST', $tweet);
    print_r($data);
} catch (Exception $e) {
    print_r($e->getMessage());
}
```
#### Author
* Twarpy is brain child of Ardalan Samimi.
