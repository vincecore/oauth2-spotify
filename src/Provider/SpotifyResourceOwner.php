<?php

namespace Vincecore\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SpotifyResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->response['id'];
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->response['display_name'];
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->response['email'];
    }

    /**
     * Return all of the owner details available as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
