<?php

namespace TracRPCTest;

use TracRPC;

class TracRPCTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TracRPC
     */
    protected $trac;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        if (false === extension_loaded('curl')) {
            $this->markTestSkipped('This test requires the PHP extension "cURL".');
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->trac);
    }

    public function testMethod_Constructor_setsProperties()
    {
        $this->trac = new TracRPC(
            'http://trac.clansuite.com/login/jsonrpc',
            array(
                'username' => 'user',
                'password' => 'password',
                'multiCall' => '1',
                'json_decode' => '1'
            )
        );

        $this->assertEquals('http://trac.clansuite.com/login/jsonrpc', $this->trac->tracURL);
        $this->assertEquals('user', $this->trac->username);
        $this->assertEquals('password', $this->trac->password);
        $this->assertTrue($this->trac->multiCall);
        $this->asserttrue($this->trac->json_decode);
    }

    /**
     * Not a mock. It's a live request.
     *
     * @expectedException Exception
     * @expectedExceptionMessage You are trying an authenticated access without providing username and password.
     */
    public function testMethod_Constructor_WithoutCredentials()
    {
        // request to "/login" without credentials
        $this->trac = new TracRPC('http://trac.clansuite.com/login/jsonrpc');
        $response = $this->trac->getWikiPage('ClansuiteTeam');
        unset($response);
    }

    /**
     * Not a mock. It's a live request.
     */
    public function testMethod_Constructor_doRequest()
    {
        $this->trac = new TracRPC('http://trac.clansuite.com/jsonrpc');
        $response = $this->trac->getWikiPage('ClansuiteTeam');

        $this->assertNotNull($response);
        $this->assertTrue(is_string($response));
        $this->assertContains('Clansuite Team', $response);
        unset($response);
    }

    /**
     * Not a mock. It's a live request.
     */
    public function testMethod_request_milestone_GetALL()
    {
        $this->trac = new TracRPC('http://trac.clansuite.com/jsonrpc');
        $response = $this->trac->getTicketMilestone();

        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
        $this->assertTrue(array_key_exists('Triage | Neuzuteilung',array_flip($response)));
        unset($response);
    }

    /**
     * Not a mock. It's a live request.
     */
    public function testMethod_request_milestone_GetOne()
    {
        $this->trac = new TracRPC('http://trac.clansuite.com/jsonrpc');
        $response = $this->trac->getTicketMilestone('get', 'Clansuite 0.2.2');

        $this->assertNotNull($response);
        $this->assertTrue(is_object($response));

        $real_response = self::objectToArray($response);

        $this->assertContains('Gettext', $real_response['description']);
        unset($response);
    }

    /**
     * Not a mock. It's a live request.
     */
    public function testMethod_request_milestone_GetOne_GetDatetime()
    {
        $this->trac = new TracRPC('http://trac.clansuite.com/jsonrpc');
        $response = $this->trac->getTicketMilestone('get', 'Clansuite 0.2.2');

        $this->assertNotNull($response);
        $this->assertTrue(is_object($response));

        $real_response = self::objectToArray($response);

        // datetime contains a string like "012-02-17T17:00:00"
        $this->assertContains('T', $real_response['due']['1']);
        unset($response);
    }

    /**
     * Converts an object into an array.
     * Handles __jsonclass__ subobject properties, too!
     *
     * @todo sub_array transformation:
     * (jsonclass ( [0] = key [1] = value ))
     * into
     * ( key => value )
     *
     * @param  type $d
     * @return type
     */
    public static function objectToArray($d = null)
    {
        /**
         * Turn object properties into array.
         */
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        /**
         * loop over all former "properties", which might be
         * objects and convert them to.
         */
        foreach ($d as $key => $value) {
            if ($key == '__jsonclass__') {
                $d = $value; #@todo sub-array transformation

                continue;
            }

            if (is_object($value)) {
                $d[$key] = self::objectToArray($value);
            }
        }

        return $d;
    }
}
