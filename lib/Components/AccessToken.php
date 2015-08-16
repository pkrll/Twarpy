<?php
/**
 * AccessToken
 *
 * Represents an immutable access token for
 * the Twitter API.
 *
 * @author Ardalan Samimi
 * @version 1.1.0
 */
namespace Twarpy\Components;

class AccessToken {

    /**
     * The access token key.
     *
     * @var string
     * @access private
     **/
    private $access_token;

    /**
     * The oauth token secret.
     *
     * @var string
     * @access private
     **/
    private $token_secret;

    /**
     * Constructor.
     *
     * @param   string  Token key.
     * @param   string  Optional. Token secret.
     * @return  object
     */
    public function __construct($tokenKey, $tokenSecret = NULL) {
        $this->access_token = $tokenKey;
        $this->token_secret = $tokenSecret;
        return $this;
    }

    /**
     * Returns the access token, either a part
     * of it, or the whole pair.
     *
     * @param   string  Specify which part to retrieve.
     * @return  array | string
     */
    public function getToken($part = NULL) {
        if (property_exists($this, $part))
            return $this->$part;
        if ($this->token_secret !== NULL)
            return array(
                "access_token" => $this->access_token,
                "token_secret" => $this->token_secret
            );
        return array(
            "access_token" => $this->access_token
        );
    }

}
?>
