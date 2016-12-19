<?php namespace squiz\surveys\controllers;

use squiz\surveys\helpers\SurveyHelper;
use squiz\utils\FormUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContentController
 * @package squiz\surveys\controllers
 */
class ContentController extends AbstractController
{
    /**
     * Action for /privacy
     */
    public function privacyAction()
    {
        $header = 'Privacy policy';

        return $this->render('privacy', [
            'previous_step_url' => $this->getPreviousUrl(),
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header)
        ]);
    }

    /**
     * Action for /terms-and-conditions
     */
    public function termsAndConditionsAction()
    {
        $header = 'What you’re agreeing to when helping to improve GOV.UK';

        return $this->render('terms-and-conditions', [
            'previous_step_url' => $this->getPreviousUrl(),
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header)
        ]);
    }

    /**
     * Action for /cookies
     */
    public function cookiesAction()
    {
        $header = 'Cookies';

        return $this->render('cookies', [
            'previous_step_url' => $this->getPreviousUrl(),
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header)
        ]);
    }
}