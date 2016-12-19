<?php namespace squiz\surveys\listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class KernelEventsListener
 * @package squiz\surveys\listeners
 */
class KernelEventsListener implements EventSubscriberInterface
{
    /**
     * Set up controllers
     * @param FilterControllerEvent $event
     */
    public function beforeControllerCalled(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $controller[0]->setRequest($event->getRequest());
        $controller[0]->setUp();
        $controller[0]->setCommonViewVariables();
    }

    /**
     * Modify response headers
     * @param FilterResponseEvent $event
     */
    public function beforeResponseReturned(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->add(array(
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-control' => 'no-store, no-cache',
            'Pragma' => 'no-cache',
            'Expires' =>  '0',
            'Content-Security-Policy' => "script-src 'self' 'unsafe-inline' www.google-analytics.com ajax.googleapis.com",
        ));
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'beforeControllerCalled',
            KernelEvents::RESPONSE => 'beforeResponseReturned',
        );
    }
}