<?php

namespace TreeHouse\BaseApiBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * A custom security context to hold information about the current user that the api client is acting on behalf of.
 */
class SecurityContext implements SecurityContextInterface
{
    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @inheritdoc
     */
    public function setToken(TokenInterface $token = null)
    {
        $this->token = $token;
    }

    /**
     * @inheritdoc
     */
    public function isGranted($attributes, $object = null)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        if (null === $object) {
            $object = $this->token->getUser();
        }

        $acceptedRoles = array_intersect($attributes, $object->getRoles());

        return (0 !== count($acceptedRoles));
    }
}
