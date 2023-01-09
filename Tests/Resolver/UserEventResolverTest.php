<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Tests\Resolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Xiidea\EasyAuditBundle\Resolver\UserEventResolver;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Common\DummyToken;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\Basic;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\DummyFilterUserResponseEvent;
use Xiidea\EasyAuditBundle\Tests\Fixtures\ORM\UserEntity;

class UserEventResolverTest extends TestCase
{
    /** @var MockObject */
    private $tokenStorage;

    /** @var UserEventResolver */
    private $eventResolver;

    public function setUp(): void    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->eventResolver = new UserEventResolver();
        $this->eventResolver->setTokenStorage($this->tokenStorage);
    }

    public function testIsAnInstanceOfEventResolverInterface()
    {
        $this->assertInstanceOf('Xiidea\EasyAuditBundle\Resolver\EventResolverInterface', $this->eventResolver);
    }

    public function testUnlistedEvent()
    {
        $auditLog = $this->eventResolver->getEventLogInfo(new Basic(), 'any_random_event');
        $this->assertEquals(array('type' => 'any_random_event', 'description' => 'any_random_event'), $auditLog);
    }

    public function testLoginEvent()
    {
        $event = new DummyFilterUserResponseEvent(new UserEntity());

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(new DummyToken(new UserEntity()));

        $auditLog = $this->eventResolver->getEventLogInfo($event, 'security.interactive_login');

        $this->assertEquals(array(
            'description' => "User 'admin' Logged in Successfully",
            'type' => 'User Logged in',
        ), $auditLog);
    }

    public function testUnknownUserEvent()
    {
        $this->assertIncompatibleEventObject('random.event');
    }

    public function testAuthenticationFailedCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('security.authentication.failure');
    }

    public function testImplicitLoginCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('fos_user.security.implicit_login');
    }

    public function testPasswordChangedCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('fos_user.change_password.edit.completed');
    }

    /**
     * @param $randomEvent
     */
    public function assertIncompatibleEventObject($randomEvent)
    {
        $event = new Basic();
        $auditLog = $this->eventResolver->getEventLogInfo($event, $randomEvent);

        $this->assertNotNull($auditLog);

        $this->assertEquals(
            array(
                'description' => $randomEvent,
                'type' => $randomEvent,
            ),
            $auditLog
        );
    }

    /**
     * @param $eventClass
     *
     * @return MockObject
     */
    protected function initializeMockEvent($eventClass)
    {
        $event = $this
            ->getMockBuilder($eventClass)
            ->disableOriginalConstructor()
            ->getMock();

        return $event;
    }
}
