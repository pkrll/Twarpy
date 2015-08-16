<?php
use Twarpy\Twarpy;

class TwarpyTest extends \PHPUnit_Framework_TestCase {

    protected $TwarpyThreeLegged;
    protected $TwarpyTwoLegged;
    protected $lastTweet;

    protected function setUp() {
        $config = array(
            'consumer_key'    => getenv('consumerKey'),
            'consumer_secret' => getenv('consumerSecret'),
            'access_token'    => getenv('oauthToken'),
            'token_secret'    => getenv('oauthTokenSecret')
        );
        $this->TwarpyThreeLegged = new Twarpy($config, 3);
        unset($config['access_token']);
        unset($config['token_secret']);
        $this->TwarpyTwoLegged = new Twarpy($config, 2);
        $this->lastTweet = 0;
    }

    /**
     */
    public function testGETWithClientCredentials() {
        try {
            $params = array("screen_name" => "twitter");
            $data = $this->TwarpyTwoLegged->request('statuses/user_timeline', 'GET', $params);
            $this->assertInternalType('array', $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     */
    public function testGETWithThreeLeggedOAuth() {
        try {
            $params = array("screen_name" => "twitter");
            $data = $this->TwarpyThreeLegged->request('statuses/user_timeline', 'GET', $params);
            $this->assertInternalType('array', $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     */
    public function testPOSTWithTreeLeggedOAuth() {
        try {
            $params = array("status" => "The Three Legged POST test 8. #twarpy");
            $data = $this->TwarpyThreeLegged->request('statuses/update', 'POST', $params);
            $this->assertInternalType('array', $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $data['id'];
    }

    /**
     * @depends testPOSTWithTreeLeggedOAuth
     */
    public function testDELETEWithTreeLeggedOAuth($tweetId) {
        try {
            $data = $this->TwarpyThreeLegged->request('statuses/destroy/'.$tweetId, "POST");
            $this->assertInternalType('array', $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


}

?>
