<?php
use Twarpy\Twarpy;

class TwarpyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testRequest() {
        $config = array(
            'consumerKey'       => getenv('consumerKey'),
            'consumerSecret'    => getenv('consumerSecret'),
            'oauthToken'    => getenv('oauthToken'),
            'oauthTokenSecret'    => getenv('oauthTokenSecret')
        );
        try {
            $twarpy = new Twarpy($config);
            $params = array("screen_name" => "twitter");
            $data = $twarpy->request('statuses/user_timeline', 'GET', $params);
            $this->assertInternalType('array', $data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}

?>
