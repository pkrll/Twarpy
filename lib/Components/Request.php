<?php
/**
 * Request
 *
 * Perform HTTP request on supplied URL.
 *
 * @author Ardalan Samimi
 * @version 1.0.0
 */
namespace Twarpy\Components;

class Request {

    /**
     * The HTTP method to use for the request.
     *
     * @var string
     * @access private
     **/
    private $httpMethod;

    /**
     * The URL to make the request to.
     *
     * @var string
     * @access private
     **/
    private $requestURL;

    /**
     * Value of the last retrieved http code.
     *
     * @var string
     * @access private
     **/
    private $lastHttpCode;

    /**
     * @param   string  The HTTP method.
     * @return  Request
     */
    public function __construct($httpMethod = NULL) {
        if ($httpMethod !== NULL)
            $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * Send the HTTP request, with optional parameters
     * $params and $header. First parameter $requestURL
     * is also optional, if the URL already been set by
     * the getRequestURL() method.
     *
     * @param   string  The request url.
     * @param   array   Optional. Parameters to post.
     * @param   array   Optional. Http header.
     * @return  array | string
     */
    public function execute($requestURL = NULL, $params = NULL, $header = NULL) {
        if ($requestURL === NULL)
            $requestURL = $this->getRequestURL();
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
        $response = Utility::convertJSON($response);
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
    public function setHttpMethod($method = "GET") {
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
    public function setRequestURL($base, $path = NULL, $query = NULL) {
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
    public function setLastHttpCode($httpCode) {
        $this->lastHttpCode = $httpCode;
    }

}
?>
