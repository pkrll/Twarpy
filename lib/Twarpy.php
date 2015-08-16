<?php
/**
 * Twarpy.
 *
 * Simple PHP Library for Twitter.
 *
 * @author Ardalan Samimi
 * @version 2.0.0
 */
namespace Twarpy;
use Twarpy\OAuth\TwoLeggedOAuth;
use Twarpy\OAuth\ThreeLeggedOAuth;
use Twarpy\Components\Consumer;
use Twarpy\Components\Request;
use Twarpy\Components\AccessToken;

require_once("config.php");

class Twarpy {

    /**
     * The Consumer object
     *
     * @var Consumer
     * @access private
     **/
    private $consumer;

    /**
     * The access token object
     *
     * @var AccessToken
     * @access private
     **/
    private $accessToken;

    /**
     * The OAuth object
     *
     * @var ThreeLeggedOAuth | TwoLeggedOAuth
     * @access private
     **/
    private $OAuth;

    /**
     * Create a Twarpy object. If no oauth_token or
     * oauth_token_secret is passed with the config
     * array, Twarpy will attempt to authorize the
     * user using a 3-legged OAuth flow.
     *
     * @param   array   An array containing the settings.
     *                  Required fields: consumerKey and
     *                  consumerSecret.
     */
    public function __construct($config, $authMethod = APP_ONLY) {
        if (!isset($config['consumer_key']) || !isset($config['consumer_secret']))
            throw new \Exception("No consumer key or consumer secret set.");
        $this->setConsumer($config['consumer_key'], $config['consumer_secret']);
        $this->setAuthMethod($authMethod);
        $this->setRequest();
        if (!isset($config['access_token'])) {
            if ($this->authorizeUser() === FALSE)
                throw new \Exception("Something went wrong. Could not get access token.");
        } else {
            $tokenSecret = (isset($config['token_secret'])) ? $config['token_secret'] : NULL;
            $this->setAccessToken($config['access_token'], $tokenSecret);
        }
    }

    /**
     * Create the request object.
     *
     * @param   Request     Optional. A request object.
     *
     */
    private function setRequest(Request $request = NULL) {
        $this->request = ($request === NULL) ? new Request() : $request;
    }

    /**
     * Creates a consumer object that will
     * hold the consumer key and secret.
     *
     * @param   string      Consumer key.
     * @param   string      Consumer secret.
     */
    private function setConsumer($key, $secret) {
        $this->consumer = new Consumer($key, $secret);
    }

    /**
     * Sets the authentication method.
     *
     * @param   APP_ONLY | THREE_LEGGED     Auth method to use.
     */
    private function setAuthMethod($authMethod) {
        $this->authMethod = $authMethod;
    }

    /**
     * Returns the current auth method.
     *
     * @return  APP_ONLY | THREE_LEGGED
     */
    public function getAuthMethod() {
        return $this->authMethod;
    }

    /**
     * Authorizes user. Called if the access token
     * was left empty upon initialization.
     *
     * @return  bool
     */
    private function authorizeUser() {
        if ($this->getAuthMethod() === THREE_LEGGED)
            $this->OAuth = new ThreeLeggedOAuth($this->consumer, $this->request);
        else
            $this->OAuth = new TwoLeggedOAuth($this->consumer, $this->request);
        if (($accessToken = $this->OAuth->getAccessToken()) === NULL)
            return FALSE;
        $tokenSecret = (isset($accessToken['token_secret'])) ? $accessToken['token_secret'] : NULL;
        $this->setAccessToken($accessToken['access_token'], $tokenSecret);
        return TRUE;
    }

    /**
     * Shorthand GET request method.
     *
     * @param   string  The request path (without .json)
     * @param   array   Optional Additional parameters.
     * @return  array | string
     */
    public function get($requestURL, array $params = NULL) {
        $this->request($requestURL, "GET", $params);
    }

    /**
     * Shorthand POST request method.
     *
     * @param   string  The request path (without .json)
     * @param   array   Optional Additional parameters.
     * @return  array | string
     */
    public function post($requestURL, array $params = NULL) {
        $this->request($requestURL, "POST", $params);
    }

    /**
     * Calls the Twitter API.
     *
     * @param   string  The request path (without .json)
     * @param   string  Optional. The http method to use
     *                  defaults to GET.
     * @param   array   Optional Additional parameters.
     * @return  array | string
     */
     public function request($requestURL, $httpMethod = 'GET', array $params = NULL) {
         $this->request->setHttpMethod($httpMethod);
         $this->request->setRequestURL(BASE_API_URL, $requestURL, $params);
         // The way to makes authenticated requests differs,
         // depending on which auth method the user chooses.
         if ($this->getAuthMethod() === THREE_LEGGED) {
             // The 3-legged auth method requires the header to have
             // all the parameters to be sent, including the access
             // token (named oauth_token).
             if ($this->OAuth === NULL)
                $this->OAuth = new ThreeLeggedOAuth($this->consumer, $this->request, $this->accessToken);
             $params['oauth_token'] = $this->getAccessToken('access_token');
             $headerParams = $this->OAuth->buildOAuth($params);
         } else {
             // The application-only auth method requires really just the
             // bearer/access token to make authenticated requests.
             if ($this->OAuth === NULL)
                $this->OAuth = new TwoLeggedOAuth($this->consumer, $this->request, $this->accessToken);
             $headerParams = $this->OAuth->getAccessToken('access_token');
         }
         // Build the header.
         $header = $this->OAuth->buildOAuthHeader($headerParams);
         // A GET requests parameters are in the query string,
         // while a POST requests should post the parameters.
         if ($httpMethod === 'GET')
             $params = NULL;
         else
             $this->request->setRequestURL($this->request->getRequestURL(TRUE));
         return $this->request->execute($this->request->getRequestURL(), $params, $header);
     }

    /**
     * Get the current rate limit status
     *
     * @return  array
     */
    public function getRateLimit() {
        $response = $this->request('application/rate_limit_status.json');
        return $response;
    }

    /**
     * Retrieve the access token.
     *
     * @param   string  Optional. Retrieves a specific
     *                  part of the access token.
     * @return  array | string
     */
    public function getAccessToken($part = NULL) {
        if ($this->accessToken !== NULL)
            return $this->accessToken->getToken($part);
        return NULL;
    }

    /**
     * Creates a new AccessToken object, holding
     * the access token (oauth token/secret or
     * bearer token).
     *
     * @param   string  The oauth_token.
     * @param   string  The oauth_token_secret.
     */
    private function setAccessToken($public, $secret) {
        $this->accessToken = new AccessToken($public, $secret);
    }

}
?>
