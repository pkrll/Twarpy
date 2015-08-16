<?php
/**
 * Consumer
 *
 * Represents an immutable consumer key and
 * secret for the Twitter API.
 *
 * @author Ardalan Samimi
 * @version 1.0.0
 */
namespace Twarpy\Components;

class Consumer {

    private $key;
    private $secret;

    function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getKey() {
        return $this->key;
    }

    public function getSecret() {
        return $this->secret;
    }
}

?>
