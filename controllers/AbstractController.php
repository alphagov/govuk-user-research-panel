<?php namespace squiz\surveys\controllers;

use squiz\surveys\listeners\ControllerListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;

/**
 * Class AbstractController
 * @package squiz\surveys\controllers
 */
abstract class AbstractController
{
    protected $variables = array();
    protected $action;
    /**
     * @var Request $request
     */
    protected $request;
    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    protected $session;
    /**
     * @var EventDispatcher $dispatcher
     */
    protected $dispatcher;
    /**
     * @var \Twig_Environment $twig
     */
    protected $twig;

    /**
     * Set a view variable
     * @param string $name
     * @param string|array $value
     */
    public function set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Method called to execute some logic before controller method is called
     */
    public function setUp()
    {
        $this->action = $this->request->get('_route');
        $this->setUpDispatcher();
        $this->setUpTemplating();
    }

    /**
     * Set up twig templates and form engine
     */
    protected function setUpTemplating()
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(array(
            'custom/squiz/surveys/views',
            'custom/squiz/surveys/fields',
            'custom/squiz/surveys/layouts',
        )), array(
            'debug' => true,
        ));
        $formEngine = new TwigRendererEngine(array('form_html.twig'));
        $formEngine->setEnvironment($this->twig);
        $this->twig->addExtension(
            new FormExtension(new TwigRenderer($formEngine))
        );
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addFilter(new \Twig_SimpleFilter('cast_to_array', function ($el) {
            return (array)$el;
        }));
    }


    /**
     * Initialize dispatcher and add subscribers
     */
    public function setUpDispatcher()
    {
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new ControllerListener());
    }

    /**
     * Get dispatcher
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Set request object
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        $this->session = $request->getSession();
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }


    /**
     * Get previous url from REFERER
     */
    protected function getPreviousUrl()
    {
        return $this->request->headers->get('referer');
//        $prev_url = '';
//        if (strpos($_SERVER['HTTP_REFERER'], FormUtils::getFormUrl($this->request->get('name'))) !== false) {
//            $prev_url = $_SERVER['HTTP_REFERER'];
//        }
//        return $prev_url;
    }

    /**
     * Set common variables available for all views rendered by controller
     */
    public function setCommonViewVariables()
    {
        $this->set('action', $this->action);
        $this->set('request', $this->request);
    }

    /**
     * Render a template using TWIG engine
     * @param null $action
     * @param array $context
     * @return Response
     */
    public function render($action = null, array $context = array())
    {
        if ($action) {
            $this->action = $action;
        }
        $context = array_merge($this->variables, $context);
        return new Response($this->twig->render($this->action.'.twig', $context));
    }
}