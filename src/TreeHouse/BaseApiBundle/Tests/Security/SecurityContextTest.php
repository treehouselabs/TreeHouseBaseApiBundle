<?php

namespace TreeHouse\BaseApiBundle\Tests\Security;

use TreeHouse\BaseApiBundle\Security\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityContext
     */
    protected $context;

    /**
     * @var UserInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    /**
     * @return array
     */
    protected function setUp()
    {
        $this->context = new SecurityContext();
        $this->user = $this
            ->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')
            ->setMethods(['getRoles'])
            ->getMockForAbstractClass()
        ;
    }

    public function testGrantedRole()
    {
        $this->user
            ->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE_USER', 'ROLE_API_USER']));

        $this->assertFalse($this->context->isGranted('ROLE_ADMIN', $this->user));
        $this->assertTrue($this->context->isGranted('ROLE_API_USER', $this->user));
    }

    public function testGrantedRoles()
    {
        $this->user
            ->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE_USER', 'ROLE_API_USER', 'ROLE_ADMIN']));

        $this->assertTrue($this->context->isGranted(['ROLE_API_USER'], $this->user));
        $this->assertTrue($this->context->isGranted(['ROLE_API_USER', 'ROLE_ADMIN'], $this->user));
        $this->assertTrue($this->context->isGranted(['ROLE_API_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->user));
    }

    public function testGrantedRoleViaToken()
    {
        $this->user
            ->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE_USER', 'ROLE_API_USER']));

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->setMethods(['getUser'])
            ->getMockForAbstractClass()
        ;
        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->user))
        ;

        $this->context->setToken($token);

        $this->assertSame($token, $this->context->getToken());
        $this->assertFalse($this->context->isGranted('ROLE_ADMIN'));
        $this->assertTrue($this->context->isGranted('ROLE_API_USER'));
    }
}
