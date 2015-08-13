## Twarpy [![Build Status](https://travis-ci.org/pkrll/Twarpy.svg?branch=master)](https://travis-ci.org/pkrll/Twarpy) [![Latest Stable Version](https://poser.pugx.org/saturn/twarpy/v/stable)](https://packagist.org/packages/saturn/twarpy) [![License](https://poser.pugx.org/saturn/twarpy/license)](https://packagist.org/packages/saturn/twarpy)
A simple and small PHP library for accessing Twitter's REST API (v1.1). For the time being, Twarpy supports only 3-legged OAuth. This is still a work in progress. More features will be added.
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
        "saturn/twarpy": "^1.0"
    }
}
```
You can also download the source directly and include it in your project directory manually, or clone this repository:
```bash
$ git clone git://github.com/pkrll/Twarpy.git
```
Do not forget to include it in your project. If you've installed Twarpy with Composer, you can use the autoloader:
```php
use Twarpy\Twarpy;
include __DIR__ . "/vendor/autoload.php";
```
#### Usage
* The first step is to register your application with [Twitter](https://apps.twitter.com).
* Copy the consumer key and consumer secret (never share these keys with anyone).
* Create the Twarpy object as shown below. Twarpy uses the 3-legged OAuth flow, which means the user must authorize the application in order to make authorized requests. When running Twarpy for the first time for a given user, the app will retrieve an access token. So set these fields to NULL if you do not have them (remember to save the tokens once you've retrieved them, by using the command ``getOAuthToken()``, preferably in a database).
```php
$config = array(
  "consumerKey"       => "YOURCONSUMERKEY",
  "consumerSecret"    => "YOURCONSUMERSECRET"
  "oauthToken"        => NULL,
  "oauthTokenSecret"  => NULL
);
// Create the Twarpy object. If no oauth token or token secret
// is set in the config array, Twarpy will first attempt to auth
// the user.
$Twarpy = new Twarpy($config);
// Save the oauth token and oauth token secret The next time you 
// run Twarpy for that user you can include the tokens in the config array.
$tokens = $Twarpy->getOAuthToken(); // returns array("oauth_token" => ???, "oauth_token_secret" => ???)
```
Making requests to the Twitter API is supereasy. All you need is the http method (GET/POST), the api path and, if needed, a parameters array. Consult the Twitter API documentation for available requests.
```php
$params = array("screen_name" => "twitter");
$data = $Twarpy->request('statuses/user_timeline', 'GET', $params);
```
#### Author
* Twarpy is brain child of Ardalan Samimi.
