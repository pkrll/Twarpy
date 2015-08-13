<?php
use Twarpy\Twarpy;

class TwarpyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     * @expectedException TwarpyException
     */
    public function testAuthAndTimeline() {
        $config = array(
            'consumerKey'       => getenv('consumerKey'),
            'consumerSecret'    => getenv('consumerSecret')
        );

        $Twarpy = new Twarpy($config);
        $this->assertInternalType('array', $Twarpy->getOAuthToken());

        $params = array("screen_name" => "twitter");
        $data = $Twitter->request('statuses/user_timeline', 'GET', $params);
        $this->assertInternalType('array', $data);
    }

}

?>
