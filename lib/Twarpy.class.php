<?php
/**
 * Twarpy.
 *
 * A Lightweight PHP Wrapper for Twitter.
 *
 * @author Ardalan Samimi
 * @version 0.1
 */
class Twarpy {

    const BASE_TOKEN_URL = "https://api.twitter.com/oauth/request_token";
    const OAUTH_AUTHORIZE = "https://api.twitter.com/oauth/authorize";
    const OAUTH_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

    private $consumerKey;
    private $consumerSecret;
    private $oauthToken;
    private $httpMethod;

    public function __construct($config) {
        foreach ($config as $key => $value)
            if (property_exists($this, $key))
                $this->$key = $value;
        if ($this->oauthToken === NULL) {
            $this->authorize();
        }
    }

    private function authorize() {
        $oauthToken     = $_GET['oauth_token'];
        $oauthVerifier  = $_GET['oauth_verifier'];
        if ($oauthVerifier === NULL) {
            $this->httpMethod = "GET";
            $response = $this->obtainRequestToken();
            if (isset($response['errors']))
                throw new Exception("Authorization failed: {$response['errors'][0]['message']}");
            // Split the response
            $response = explode("&", $response);
            foreach($response as $key => $value) {
                $value = explode("=", $value);
                $requestToken[$value[0]] = $value[1];
            }
            $this->oauthToken = $requestToken['oauth_token'];
            header("Location: " . self::OAUTH_AUTHORIZE . '?' . 'oauth_token=' . $this->oauthToken);
        } else {
            $oauth = $this->buildOauth();
            $header[] = "User-Agent: Twarpy";
            foreach($oauth as $key => $value)
                $authHeader[] = $key . "=\"" . rawurlencode($value) . "\"";
            $header[] = "Authorization: OAuth " . implode(",", $authHeader);
            $header[] = "Content-Type: application/x-www-form-urlencoded";
            $this->httpMethod = "POST";
            $request = self::OAUTH_ACCESS_TOKEN;
            $params['oauth_verifier'] = $oauthVerifier;
            $response = $this->makeRequest($request, $params);
            print_r($response);

        }
    }

    private function makeRequest($request, $params = NULL) {
        $curlHandle = curl_init();
        $curlOption = array(
            CURLOPT_URL => $request,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_POST => ($this->httpMethod === 'POST')
        );
        curl_setopt_array($curlHandle, $curlOption);
        if ($params !== NULL)
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);
        return $response;
    }

    private function obtainRequestToken() {
        // Retrieve the oauth
        $oauth = $this->buildOauth();
        // Build the request URL
        foreach ($oauth as $key => $value)
            $request[] = $key . '=' . $value;
        $request = self::BASE_TOKEN_URL . "?" . implode('&', $request);
        $response = $this->makeRequest($request);
        return $response;
    }

    private function buildOauth() {
        $oauth = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => md5(time()),
            'oauth_signature_method' => "HMAC-SHA1",
            'oauth_timestamp' => time(),
            'oauth_version' => "1.0"
        );
        ksort($oauth);
        $oauth['oauth_signature'] = $this->buildOAuthSignature($oauth);
        ksort($oauth);
        return $oauth;
    }

    private function buildOAuthSignature($oauth) {
        foreach($oauth as $key => $value)
            $signatureBase[] = rawurlencode($key) . '=' . rawurlencode($value);
        $signatureBase  = $this->httpMethod . '&' . rawurlencode(self::BASE_TOKEN_URL) . '&' . rawurlencode(implode('&', $signatureBase));
        $signatureKey   = rawurlencode($this->consumerSecret) . '&';
        $oauthSignature = rawurlencode(base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, TRUE)));
        return $oauthSignature;
    }

}

?>
