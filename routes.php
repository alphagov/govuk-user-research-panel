<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use squiz\surveys\controllers\SurveysController;
use squiz\surveys\controllers\ContentController;
use squiz\surveys\controllers\RegistrationController;

$routes = new RouteCollection();
$routes->add('survey', new Route('/{step}/{mode}', array(
    '_controller' => array(new SurveysController(), 'surveyAction'),
    'step' => 1,
    'mode' => '',
), array(
    'step' => '\d+',
    'mode' => 'change|',
), array(), // options
    null, // host
    array(), // schemes
    array('GET')
));
$routes->add('submit-step', new Route('/{step}/{mode}', array(
    '_controller' => array(new SurveysController(), 'surveySubmitStepAction'),
    'step' => 1,
    'mode' => '',
), array(
    'step' => '\d+',
    'mode' => 'change|',
), array(), null, array(), array('POST')));
$routes->add('check-answers', new Route('/check-answers', array(
    '_controller' => array(new SurveysController(), 'checkAnswersAction'),
)));
$routes->add('exit-preview', new Route('/exit-preview', array(
    '_controller' => array(new SurveysController(), 'exitPreviewAction'),
)));
$routes->add('completed', new Route('/completed/{contact_id}', array(
    '_controller' => array(new SurveysController(), 'completedAction'),
)));
$routes->add('terms-and-conditions', new Route('/terms-and-conditions', array(
    '_controller' => array(new ContentController(), 'termsAndConditionsAction'),
)));
$routes->add('privacy', new Route('/privacy', array(
    '_controller' => array(new ContentController(), 'privacyAction'),
)));
$routes->add('cookies', new Route('/cookies', array(
    '_controller' => array(new ContentController(), 'cookiesAction'),
)));
$routes->add('declaration', new Route('/declaration', array(
    '_controller' => array(new SurveysController(), 'declarationAction'),
)));

$routes->add('survey-submit', new Route('/submit', array(
    '_controller' => array(new SurveysController(), 'surveySubmitAction'),
), array(), array(), null, array(), array('POST')));

if (!ONBOARDING) {
    $routes->addPrefix('/{name}');
    $masterCollection = new RouteCollection();
    $masterCollection->add('terms-and-conditions-content', new Route('/terms-and-conditions', array(
        '_controller' => array(new ContentController(), 'termsAndConditionsAction'),
    )));
    $masterCollection->add('privacy-content', new Route('/privacy', array(
        '_controller' => array(new ContentController(), 'privacyAction'),
    )));
    $masterCollection->add('cookies-content', new Route('/cookies', array(
        '_controller' => array(new ContentController(), 'cookiesAction'),
    )));
    $masterCollection->addCollection($routes);
    return $masterCollection;
} else {
    $routes->add('contact', new Route('/contact', array(
        '_controller' => array(new RegistrationController(), 'contactAction'),
    )));
    $routes->add('registration-submit', new Route('/register', array(
        '_controller' => array(new RegistrationController(), 'registrationSubmitAction'),
    ), array(), array(), null, array(), array('POST')));
    $routes->add('home', new Route('/home', array(
        '_controller' => array(new RegistrationController(), 'homeAction'),
    )));
    $routes->add('confirm-email', new Route('/confirm-email/{contact}', array(
        '_controller' => array(new RegistrationController(), 'confirmEmailAction'),
    )));
    $routes->add('email-confirmed', new Route('/email-confirmed', array(
        '_controller' => array(new RegistrationController(), 'emailConfirmedAction'),
    )));

    $routes->add('resend-email', new Route('/resend-email/{contact}', array(
        '_controller' => array(new RegistrationController(), 'resendEmailAction'),
    )));
    $routes->add('verify', new Route('/v/{hash}', array(
        '_controller' => array(new RegistrationController(), 'verifyAction'),
    )));
    $routes->add('more', new Route('/more', array(
        '_controller' => array(new RegistrationController(), 'moreAction'),
    )));
    $routes->add('unsubscribe-reason-submit', new Route('/unsubscribe-reson/{optout_id}', array(
        '_controller' => array(new RegistrationController(), 'unsubscribeReasonSubmitAction'),
    ), array(), array(), null, array(), array('POST')));
    $routes->add('unsubscribe', new Route('/unsubscribe/{optout_id}', array(
        '_controller' => array(new RegistrationController(), 'unsubscribeReasonAction'),
    ), array(), array(), null, array(), array('GET')));
    $routes->add('unsubscribe-process', new Route('/u/{hash}', array(
        '_controller' => array(new RegistrationController(), 'unsubscribeAction'),
    )));
    $routes->add('unsubscribe-success', new Route('/unsubscribe-success', array(
        '_controller' => array(new RegistrationController(), 'unsubscribeReasonThankYouAction'),
    )));
    $routes->addDefaults(array('name' => $request->query->get('name')));
    return $routes;
}