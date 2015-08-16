<?php
/**
 * OAuth parent class.
 *
 * @author Ardalan Samimi
 * @version 1.0.0
 */
namespace Twarpy\OAuth;
use \Twarpy\Components\Consumer;
use \Twarpy\Components\Request;
use \Twarpy\Components\AccessToken;

class OAuth {

    /**
     * The Request object
     *
     * @var Request
     * @access protected
     **/
    protected $request;

    /**
     * The Consumer object
     *
     * @var Consumer
     * @access protected
     **/
    protected $consumer;

    /**
     * The AccessToken object
     *
     * @var AccessToken
     * @access protected
     **/
    protected $accessToken;

    function __construct(Consumer $consumer = NULL, Request $request = NULL) {
        if ($consumer === NULL)
            throw new \Exception("Authentication failed. Missing consumer key and secret.");
        $this->consumer = $consumer;
        $this->request = $request;
    }

}
?>
