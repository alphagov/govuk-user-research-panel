<?php namespace squiz\surveys;

use Symfony\Component\Routing;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use squiz\surveys\listeners\KernelEventsListener;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class Framework
 * @package squiz\surveys
 */
class Framework extends HttpKernel\HttpKernel
{
    protected $matcher;
    protected $resolver;

    /**
     * Framework constructor.
     * @param RouteCollection $routes
     * @param Request $request
     */
    public function __construct(RouteCollection $routes, Request $request)
    {
        $context = new Routing\RequestContext();
        $context->fromRequest($request);
        $matcher = new Routing\Matcher\UrlMatcher($routes, $context);
        $resolver = new HttpKernel\Controller\ControllerResolver();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new HttpKernel\EventListener\RouterListener($matcher));
        $dispatcher->addSubscriber(new HttpKernel\EventListener\ResponseListener('UTF-8'));
        $dispatcher->addSubscriber(new KernelEventsListener());
        $listener = new HttpKernel\EventListener\ExceptionListener(
            'squiz\\surveys\\controllers\\ExceptionController::handleError'
        );
        $dispatcher->addSubscriber($listener);

        parent::__construct($dispatcher, $resolver);
    }
}