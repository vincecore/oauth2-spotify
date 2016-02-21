<?php

namespace Vincecore\OAuth2\Client\Test\Provider;

use Mockery as m;
use Vincecore\OAuth2\Client\Provider\Spotify;

class SpotifyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Spotify
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new Spotify(
            array(
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            )
        );
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl(
            array('show_dialog' => true)
        );
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('show_dialog', $query);
        $this->assertEquals($query['show_dialog'], true);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = ['scope' => [uniqid(),uniqid()]];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(',', $options['scope'])), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/api/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "expires_in":"11111", "token_type":"bearer", "refresh_token":"token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(time() + 11111, $token->getExpires());
        $this->assertEquals('token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $userId = rand(1000, 9999);
        $name = uniqid();
        $email = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&state=123');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"id": '.$userId.', "display_name": "'.$name.'", "email": "'.$email.'"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['id']);
        $this->assertEquals($name, $user->getDisplayName());
        $this->assertEquals($name, $user->toArray()['display_name']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['email']);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $this->markTestSkipped('TODO');

        $message = uniqid();
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(' {"error":"'.$message.'"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
