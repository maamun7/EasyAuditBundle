<?php
/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineListener
{
    /**
     * @var array
     */
    private $entity_class;
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, $entity_class)
    {
        $this->entity_class = $entity_class;
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof $this->entity_class) {
            $entity->setEventTime(new \DateTime());
            $this->setUser($entity);
        }
    }

    private function setUser($entity)
    {
        $userProperty = $this->container->getParameter('xiidea.easy_audit.user_property');

        if (empty($userProperty)) {
            $entity->setUser($this->getUser());
        } elseif (is_callable($entity, "get{$userProperty}")) {
            $propertyGetter = "get{$userProperty}";
            $entity->setUser($entity->$propertyGetter());
        } elseif($this->isDebug()) {
            throw new \Exception("get{$userProperty}() not found in user object");
        }
    }

    private function isDebug()
    {
        return $this->container->get('kernel')->isDebug();
    }

    /**
     * Get a user from the Security Context
     *
     * @return mixed
     * @throws \LogicException If SecurityBundle is not available
     */
    public function getUser()
    {
        if (!$this->container->has('security.context')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.context')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    public function getUsername()
    {
        $user = $this->getUser();

        if($user == null){
            return 'Anonymous';
        }

        return $user->getUsername();
    }
}