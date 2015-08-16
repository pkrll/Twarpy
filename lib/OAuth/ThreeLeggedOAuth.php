<?php
/**
 * 3-Legged OAuth
 *
 * For Twitter request requiring a three legged OAuth,
 * which usually are request to API for endpoints that
 * require user context, such as deletion and posting.
 *
 * @author Ardalan Samimi
 * @version 1.0.0
 */
namespace Twarpy\OAuth;
use \Twarpy\Components\Consumer;
use \Twarpy\Components\Request;
use \Twarpy\Components\AccessToken;
use \Twarpy\Components\Utility;

class ThreeLeggedOAuth extends OAuth {

    /**
     * Constructor. Will automatically try to authenticate
     * the user if the AccessToken object is not set.
     *
     * @param   Consumer    A Consumer object.
     * @param   Request     A Request object.
     * @param   AccessToken A AccessToken object.
     */
    function __construct(Consumer $consumer = NULL, Request $request = NULL, AccessToken $accessToken = NULL) {
        parent::__construct($consumer, $request);
        if ($accessToken === NULL)
            $accessToken = $this->authorize();
        if ($accessToken instanceof AccessToken)
            $this->accessToken = $accessToken;
        else
            $this->setAccessToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
    }

    /**
     * Build the header string.
     *
     * @param   array   OAuth parameters.
     * @return  array
     */
    public function buildOAuthHeader($oauth) {
        $header[] = "Accept: application/json";
        $header[] = "User-Agent: " . APP_NAME;
        foreach ($oauth as $key => $value)
            $authHeader[] = $key . "=\"" . rawurlencode($value) . "\"";
        $header[] = "Authorization: OAuth " . implode(", ", $authHeader);
        $header[] = "Content-Type: application/x-www-form-urlencoded";
        return $header;
    }

    /**
     * Create the OAuth parameter.
     *
     * @param   array   Optional. Additional request parameters.
     * @return  array
     */
    public function buildOAuth($params = NULL) {
        $oauth = array(
            'oauth_consumer_key' => $this->consumer->getKey(),
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
     * Set the access token.
     *
     * @param   string  The oauth_token.
     * @param   string  The oauth_token_secret.
     */
    private function setAccessToken($public, $secret) {
        $this->accessToken = new AccessToken($public, $secret);
    }

    /**
     * Authenticate the user, using 3-legged
     * OAuth flow. Called if an access token
     * was not passed along upon initialization.
     *
     * @return  void
     */
    private function authorize() {
        $params = array(
            "oauth_token"    => (isset($_GET['oauth_token'])) ? $_GET['oauth_token'] : NULL,
            "oauth_verifier" => (isset($_GET['oauth_verifier'])) ? $_GET['oauth_verifier'] : NULL
        );
        if ($params['oauth_token'] === NULL || $params['oauth_verifier'] === NULL)
            $this->obtainRequestToken();
        else
            return $this->obtainAccessToken($params);
        return NULL;
    }

    /**
     * Obtain a request token. Redirects user to
     * authenticate app, which then returns the
     * request token in the query string.
     *
     * @return  void
     */
    private function obtainRequestToken() {
        $this->request->setHttpMethod("GET");
        $this->request->setRequestURL(REQUEST_TOKEN_URL);
        // Retrieve the OAuth parameters
        $oauth = $this->buildOAuth();
        // Build the request url
        foreach($oauth as $key => $value)
            $request[] = $key . '=' . $value;
        $request = REQUEST_TOKEN_URL . "?" . implode('&', $request);
        $response = $this->request->execute($request);
        // Check if authorization failed
        if (isset($response["errors"]))
            throw new \Exception("Authorization failed: {$response['errors'][0]['message']}");
        // The response will include three parameters
        // that needs to be split into an array.
        $requestToken = Utility::splitQueryString($response);
        // Redirect user to authenticate app
        $redirectURL = AUTHORIZE_URL . '?' . 'oauth_token=' . $requestToken['oauth_token'];
        header("Location: " . $redirectURL);
    }

    /**
     * Obtain and set the access token/secret, by converting
     * the request token set in the get parameters.
     *
     * @return  void
     */
    private function obtainAccessToken($params) {
        $this->request->setHttpMethod("POST");
        // The oauth token parameter received must be
        // included in the signing key when retrieving
        // the access token.
        $oauth  = $this->buildOAuth(array("oauth_token" => $params['oauth_token']));
        $header = $this->buildOAuthHeader($oauth);
        // The verifier key must also be passed along.
        $params = array(
            "oauth_verifier" => $params['oauth_verifier']
        );
        $response = $this->request->execute(ACCESS_TOKEN_URL, $params, $header);
        if ($this->request->getLastHttpCode() !== 200)
            throw new \Exception("Authorization failed: {$response}");
        // Retrieve the access token and token secret
        $tokens = Utility::splitQueryString($response);
        return $tokens;
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
        $signatureBase = $this->request->getHttpMethod() . '&' . rawurlencode($this->request->getRequestURL(TRUE)) . '&' . rawurlencode(implode('&', $signatureBase));
        $signatureKey = rawurlencode($this->consumer->getSecret()) . '&';
        // When not obtaining an access or request token the
        // signing key must contain the oauth token secret.
        if ($this->request->getRequestURL() !== REQUEST_TOKEN_URL &&
            $this->request->getRequestURL() !== ACCESS_TOKEN_URL) {
            $signatureKey .= rawurlencode($this->getAccessToken('token_secret'));
            return base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, TRUE));
        }
        // The signing key must be percent coded when obtaining
        // an access or request token.
        return rawurlencode(base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, TRUE)));
    }

}
?>
