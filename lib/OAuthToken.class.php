<?php
/**
 * Access token object
 *
 * @author Ardalan Samimi
 * @version 1.0
 */
class OAuthToken {

    private $oauth_token;
    private $oauth_token_secret;

    public function __construct($tokenKey, $tokenSecret) {
        $this->oauth_token = $tokenKey;
        $this->oauth_token_secret = $tokenSecret;
    }

    public function getOAuthToken($part) {
        if (property_exists($this, $part))
            return $this->$part;
        return array(
            "oauth_token" => $this->oauth_token,
            "oauth_token_secret" => $this->oauth_token_secret
        );
    }

}

?>
