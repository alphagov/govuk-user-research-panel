<?php namespace squiz\surveys\controllers;

use squiz\surveys\helpers\SurveyHelper;
use Symfony\Component\Debug\Exception\FlattenException;
use squiz\utils\FormUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExceptionController
 * @package squiz\surveys\controllers
 */
class ExceptionController extends AbstractController
{
    /**
     * Handle errors
     * @param FlattenException $exception
     * @return Response
     */
    public function handleError(FlattenException $exception)
    {
        if ($exception->getClass() == 'squiz\\surveys\\exceptions\\SessionExpiredException') {
            return $this->handleSessionExpired();
        }
        $log = \LoggerManager::getLogger();
        $log->fatal('Exception: '.$exception->getClass());
        $log->fatal('Message: '.$exception->getMessage());
        switch ($exception->getStatusCode()) {
            case '404':
                return $this->handle404();
            case '500':
            default:
                return $this->handle500();
        }
    }

    /**
     * Handle 404 error
     * @return Response
     */
    public function handle404()
    {
        $header = 'This page cannot be found';

        return $this->render('page-not-found', [
            'header' => $header,
            'survey_title', SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Handle 500 error
     * @return Response
     */
    public function handle500()
    {
        $header = 'This page cannot be found';

        return $this->render('page-not-found', [
            'header' => $header,
            'survey_title', SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Handle Session Expired error
     * @return Response
     */
    public function handleSessionExpired()
    {
        $header = 'Session expired';

        return $this->render('session-expired', [
            'header' => $header,
            'survey_title', SurveyHelper::getPageTitle($header),
        ]);
    }
}