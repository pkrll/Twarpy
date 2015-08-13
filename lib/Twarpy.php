<?php
/**
 * Twarpy.
 *
 * Lightweight PHP Library for Twitter.
 *
 * @author Ardalan Samimi
 * @version 1.0
 */
namespace Twarpy;

require_once("OAuthToken.php");

class Twarpy {

    const APP_NAME = "Twarpy";

    const API_URL           = "https://api.twitter.com/1.1/";
    const REQUEST_TOKEN_URL = "https://api.twitter.com/oauth/request_token";
    const AUTHORIZE_URL     = "https://api.twitter.com/oauth/authorize";
    const ACCESS_TOKEN_URL  = 'https://api.twitter.com/oauth/access_token';

    private $consumerKey;
    private $consumerSecret;
    private $accessToken;

    private $httpMethod;
    private $requestURL;
    private $lastHttpCode;

    /**
     * Constructor.
     *
     * Create a Twarpy object. If no oauth_token or
     * oauth_token_secret is passed with the config
     * array, Twarpy will attempt to authorize the
     * user using a 3-legged OAuth flow.
     *
     * @param   array   An array containing the settings.
     *                  Required fields: consumerKey and
     *                  consumerSecret.
     */
    public function __construct($config) {
        foreach ($config as $key => $value)
            if (property_exists($this, $key))
                $this->$key = $value;
        if ($this->consumerKey === NULL || $this->consumerSecret === NULL)
            throw new Exception("No consumer key or consumer secret set.");
        if (!isset($config['oauthToken']) || !isset($config['oauthTokenSecret'])) {
            if ($this->authorization() === FALSE) {
                throw new Exception("Authorization failed. An error occured.");
            }
        } else {
            $this->setOAuthToken($config['oauthToken'], $config['oauthTokenSecret']);
        }
    }

    /**
     * Public API call method.
     *
     * @param   string  The request path (without .json)
     * @param   string  Optional. The http method to use
     *                  defaults to GET.
     * @param   array   Additional parameters to send.
     * @return  array | string
     */
    public function request($request, $httpMethod = 'GET', array $params = NULL) {
        $this->setHttpMethod($httpMethod);
        $this->setRequestURL(self::API_URL, $request, $params);
        $params['oauth_token'] = $this->getOAuthToken('oauth_token');
        $oauth = $this->buildOAuth($params);
        $header = $this->buildOAuthHeader($oauth);
        // A GET request's parameters are in the query string,
        // while a POST request will post the parameters.
        if ($httpMethod === 'GET')
            $params = NULL;
        else
            $this->setRequestURL($this->getRequestURL(TRUE));
        $response = $this->makeRequest($this->getRequestURL(), $params, $header);
        return $response;
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
     * Get the HTTP Method to be used or last used.
     *
     * @return  string
     */
    public function getHttpMethod() {
        return $this->httpMethod;
    }

    /**
     * Set the HTTP Method.
     *
     * @param   string  The HTTP request method (GET, POST)
     */
    private function setHttpMethod($method = "GET") {
        $this->httpMethod = strtoupper($method);
    }

    /**
     * Get the request URL. If boolean false is given, the
     * url will be returned without a query string.
     *
     * @param   bool    If set true, removes query string.
     * @return  string
     */
    public function getRequestURL($removeQueryString = FALSE) {
        if ($removeQueryString)
            return preg_replace('/\?.*/', "", $this->requestURL);
        return $this->requestURL;
    }

    /**
     * Set the request URL. Query strings must be passed
     * seperately for the signing process to work.
     *
     * @param   string  The base URL string.
     * @param   string  Optional. The URL path, without
     *                  the query string.
     * @param   string  Optional. A query string.
     */
    private function setRequestURL($base, $path = NULL, $query = NULL) {
        if ($path !== NULL) {
            // Add .json extension if not already there.
            if (strpos($path, '.json') === FALSE)
                $path .= '.json';
            $base .= $path;
        }
        // The query string must be an array.
        if ($query !== NULL && is_array($query))
            $base .= '?' . http_build_query($query);
        $this->requestURL = $base;
    }

    /**
     * Get the last retrieved HTTP Code.
     *
     * @return  int
     */
    public function getLastHttpCode() {
        return $this->lastHttpCode;
    }

    /**
     * Set the last retrieved HTTP code.
     *
     * @param   int     An integer.
     */
    private function setLastHttpCode($httpCode) {
        $this->lastHttpCode = $httpCode;
    }

    /**
     * Retrieve the access token.
     *
     * @param   string  Optional. Retrieves a specific
     *                  part of the access token.
     * @return  array | string
     */
    public function getOAuthToken($part = NULL) {
        return $this->accessToken->getOAuthToken($part);
    }

    /**
     * Set the access token
     *
     * @param   string  The oauth_token.
     * @param   string  The oauth_token_secret.
     */
    private function setOAuthToken($public, $secret) {
        $this->accessToken = new OAuthToken($public, $secret);
    }

    /**
     * Make the HTTP request.
     *
     * @param   string  The request url.
     * @param   array   Parameters to post.
     * @param   array   Http header.
     * @return  array | string
     */
    private function makeRequest($requestURL, $params = NULL, $header = NULL) {
        $curlHandle = curl_init();
        // Default curl options array
        $curlOptions = array(
            CURLOPT_URL => $requestURL,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HEADER         => 0,
            CURLOPT_POST           => ($this->getHttpMethod() === 'POST')
        );
        curl_setopt_array($curlHandle, $curlOptions);
        // The parameters array must be turned into an URL-encoded query string.
        if ($params !== NULL && $this->getHttpMethod() === 'POST') {
            // The access token should not be included in the post data.
            if (array_key_exists("oauth_token", $params))
                unset($params['oauth_token']);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        if ($header !== NULL)
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curlHandle);
        // Save the received http code.
        $this->setLastHttpCode(curl_getinfo($curlHandle, CURLINFO_HTTP_CODE));
        curl_close($curlHandle);
        // If the response is in JSON, convert it to an array
        $response = $this->convertJSON($response);
        return $response;
    }

    /**
     * Authorize user using 3-legged OAuth flow.
     *
     * @return  bool
     */
    private function authorization() {
        $oauthToken     = (isset($_GET['oauth_token'])) ? $_GET['oauth_token'] : NULL;
        $oauthVerifier  = (isset($_GET['oauth_verifier'])) ? $_GET['oauth_verifier'] : NULL;
        if ($oauthToken === NULL || $oauthVerifier === NULL) {
            $this->setHttpMethod("GET");
            $this->setRequestURL(self::REQUEST_TOKEN_URL);
            $response = $this->fetchRequestToken();
            // Check if authorization failed
            if (isset($response["error"]))
                throw new Exception("Authorization failed: {$response['errors'][0]['message']}");
            // The response will include three parameters
            // that needs to be split into an array.
            $requestToken = $this->splitQueryString($response);
            // Redirect user to authenticate app
            $redirectURL = self::AUTHORIZE_URL . '?' . 'oauth_token=' . $requestToken['oauth_token'];
            header("Location: " . $redirectURL);
        } else {
            $this->setHttpMethod("POST");
            $this->setRequestURL(self::ACCESS_TOKEN_URL);
            // The oauth token parameter received must be
            // included in the signing key when retrieving
            //  the access token.
            $oauth  = $this->buildOAuth(array("oauth_token" => $oauthToken));
            $header = $this->buildOAuthHeader($oauth);
            // The verifier key must also be passed along.
            $params = array(
                "oauth_verifier" => $oauthVerifier
            );
            $response = $this->makeRequest($this->getRequestURL(), $params, $header);
            if ($this->getLastHttpCode() !== 200)
                throw new Exception("Authorization failed: {$response}");
            // Retrieve the access token and token secret
            $tokens = $this->splitQueryString($response);
            $this->setOAuthToken($tokens['oauth_token'], $tokens['oauth_token_secret']);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Obtain a request token. Called only by the
     * method authorization() when trying to auth
     * user. Returns a request token that will be
     * be converted to an access token.
     *
     * @return  string
     */
    private function fetchRequestToken() {
        // Retrieve the OAuth parameters
        $oauth = $this->buildOAuth();
        // Build the request url
        foreach($oauth as $key => $value)
            $request[] = $key . '=' . $value;
        $request = self::REQUEST_TOKEN_URL . "?" . implode('&', $request);
        return $this->makeRequest($request);
    }

    /**
     * Create the OAuth parameter.
     *
     * @param   array   Optional. Additional request parameters.
     * @return  array
     */
    private function buildOAuth($params = NULL) {
        $oauth = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => md5(time()),
            'oauth_signature_method' => "HMAC-SHA1",
            'oauth_timestamp' => time(),
            'oauth_version' => "1.0"
        );
        // Add Additional parameters
        if ($params !== NULL)
            foreach($params as $key => $value)
                $oauth[$key] = $value;
        // The parameters must be sorted alphabetically
        // by key, before creating the oauth signature.
        ksort($oauth);
        $oauth['oauth_signature'] = $this->buildSignature($oauth);
        ksort($oauth);
        return $oauth;
    }

    /**
     * Build the oauth signature for authorized requests.
     *
     * @param   array   The request parameters.
     * @return  array
     */
    private function buildSignature($oauth) {
        foreach($oauth as $key => $value)
            $signatureBase[] = rawurlencode($key) . '=' . rawurlencode($value);
        $signatureBase = $this->getHttpMethod() . '&' . rawurlencode($this->getRequestURL(TRUE)) . '&' . rawurlencode(implode('&', $signatureBase));
        $signatureKey = rawurlencode($this->consumerSecret) . '&';
        // When not obtaining an access or request token the
        // signing key must contain the oauth token secret.
        if ($this->getRequestURL() !== self::REQUEST_TOKEN_URL &&
            $this->getRequestURL() !== self::ACCESS_TOKEN_URL) {
            $signatureKey .= rawurlencode($this->getOAuthToken('oauth_token_secret'));
            return base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, TRUE));
        }
        // The signing key must be percent coded when obtaining
        // an access or request token.
        return rawurlencode(base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, TRUE)));
    }

    /**
     * Build the header string.
     *
     * @param   array   OAuth parameters.
     * @return  array
     */
    private function buildOAuthHeader($oauth) {
        $header[] = "Accept: application/json";
        $header[] = "User-Agent: " . self::APP_NAME;
        foreach ($oauth as $key => $value)
            $authHeader[] = $key . "=\"" . rawurlencode($value) . "\"";
        $header[] = "Authorization: OAuth " . implode(", ", $authHeader);
        $header[] = "Content-Type: application/x-www-form-urlencoded";
        return $header;
    }

    /**
     * Splits a query string into an array.
     *
     * @param   string  String to split.
     * @return  array
     */
    private function splitQueryString($string) {
        $string = explode("&", $string);
        $array  = NULL;
        foreach($string as $key => $value) {
            $value = explode("=", $value);
            $array[$value[0]] = $value[1];
        }
        return $array;
    }

    /**
     * Convert a JSON string to an associative
     * array. The string will not be altered if
     * it is not JSON.
     *
     * @param   string  The JSON string to convert.
     * @return  array | string
     */
    private function convertJSON($json) {
        $array = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE)
            return $array;
        return $json;
    }

}


?>
