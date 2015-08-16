<?php
/**
 * Application-only authentication
 *
 * For authenticated requests that does not need user
 * context. All authenticated requests are made on behalf
 * of the app itself.
 *
 * @author Ardalan Samimi
 * @version 1.0.0
 */
namespace Twarpy\OAuth;
use \Twarpy\Components\Consumer;
use \Twarpy\Components\Request;
use \Twarpy\Components\AccessToken;

class TwoLeggedOAuth extends OAuth {

    /**
     * Constructor. will automatically try to authenticate
     * the user if the AccessToken object is not set.
     *
     * @param   Consumer    A Consumer object.
     * @param   Request     A Request object.
     * @param   AccessToken A AccessToken object.
     */
    function __construct(Consumer $consumer = NULL, Request $request = NULL, AccessToken $accessToken = NULL) {
        parent::__construct($consumer, $request);
        if ($accessToken === NULL)
            $accessToken = $this->obtainBearerToken();
        $this->setAccessToken($accessToken);
    }

    /**
     * Create the OAuth parameter.
     *
     * @param   string  The bearer token or token credentials.
     * @param   bool    Optional. Determines the token type.
     * @return  array
     */
    public function buildOAuthHeader($bearerToken, $authenticated = TRUE) {
        $header[] = "Accept: application/json";
        $header[] = "User-Agent: " . APP_NAME;
        if ($authenticated)
            $header[] = "Authorization: Bearer " . $bearerToken;
        else
            $header[] = "Authorization: Basic " . $bearerToken;
        $header[] = "Content-Type: application/x-www-form-urlencoded;charset=UTF-8";
        return $header;
    }

    /**
     * Retrieve the access token.
     *
     * @return  array | string
     */
    public function getAccessToken($part = NULL) {
        return $this->accessToken->getToken($part);
    }

    /**
     * Set the access token
     *
     * @param   string  The bearer token.
     */
    private function setAccessToken($token) {
        if ($token instanceof AccessToken)
            $this->accessToken = $token;
        elseif (is_string($token))
            $this->accessToken = new AccessToken($token);
        else
            throw new \Exception("Could not set access token. Wrong type.");

    }

    /**
     * Obtain a bearer token.
     *
     * @return  string
     */
    private function obtainBearerToken() {
        if ($this->request === NULL)
            $this->request = new Request();
        $bearerTokenCredentials = $this->getCredentials();
        $this->request->setHttpMethod("POST");
        $header = $this->buildOAuthHeader($bearerTokenCredentials, FALSE);
        $params = array("grant_type" => "client_credentials");
        $response = $this->request->execute(BEARER_TOKEN_URL, $params, $header);
        if (isset($response['errors']))
            throw new \Exception("Authorization failed: {$response['errors'][0]['message']}");
        if (isset($response['token_type']) && $response['token_type'] == "bearer")
            return $response['access_token'];
    }

    /**
     * Creates the bearer token credentials string.
     *
     * @return  string
     */
    private function getCredentials() {
        return base64_encode(rawurlencode($this->consumer->getKey()) . ':' . rawurlencode($this->consumer->getSecret()));
    }

}
?>
