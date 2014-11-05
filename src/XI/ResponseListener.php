<?php

namespace XI;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Yakub Kristianto <yakub1986@gmail.com>
 */

class ResponseListener implements EventSubscriberInterface
{
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        if (!$result instanceof Response) {
            $event->setResponse(new Response());
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array(array('onView', 64)),
        );
    }
}
