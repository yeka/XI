<?php

namespace XI;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Yakub Kristianto <yakub1986@gmail.com>
 */

class ResponseListener implements EventSubscriberInterface
{
    private $apppath;

    public function __construct($apppath)
    {
        $this->apppath = $apppath;
    }

    public function onController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller[0] instanceof \XI\Controller) {
            $controller[0]->setAppPath($this->apppath);
        }
    }

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
            KernelEvents::CONTROLLER => array(array('onController', 64)),
        );
    }
}
