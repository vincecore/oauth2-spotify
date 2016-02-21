<?php

namespace Vincecore\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Spotify extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * {@inheritdoc}
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://accounts.spotify.com/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://accounts.spotify.com/api/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.spotify.com/v1/me';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultScopes()
    {
        return array();
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $data['error'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new SpotifyResourceOwner($response);
    }
}
