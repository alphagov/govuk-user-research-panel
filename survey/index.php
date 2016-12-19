<?php
if(!defined('sugarEntry')) define('sugarEntry', true);
//change directory to sugar root directory
chdir('../../../../');
require_once('include/entryPoint.php');
require_once('include/Expressions/Expression/Parser/Parser.php');
require_once('include/utils/db_utils.php');
require_once('include/SugarFields/SugarFieldHandler.php');

global $app_list_strings, $current_user;

$app_list_strings = return_app_list_strings_language('en_us');
$current_user = BeanFactory::getBean('Users');
$current_user->getSystemUser();

const ONBOARDING = false;

use Symfony\Component\HttpFoundation\Request;
use squiz\surveys\lib\SessionFactory;

$request = Request::createFromGlobals();
$session = SessionFactory::getInstance();
$session->start();
$request->setSession($session);
$routes = include __DIR__.'/../routes.php';
$framework = new squiz\surveys\Framework($routes, $request);
$framework->handle($request)->send();
