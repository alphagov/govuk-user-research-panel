<?php namespace squiz\surveys\events;

use squiz\surveys\models\FormModel;
use Symfony\Component\EventDispatcher\Event;
use squiz\surveys\entities\SurveyResultsCollection;

/**
 * Class AfterSurveySubmittedEvent
 * @package squiz\surveys\events
 */
class AfterSurveySubmittedEvent extends Event
{
    const NAME = 'survey.submitted.after';

    /**
     * @var FormModel
     */
    protected $model;
    /**
     * @var \SugarBean
     */
    protected $contact;
    /**
     * @var SurveyResultsCollection
     */
    protected $surveyResults;

    /**
     * AfterSurveySubmittedEvent constructor.
     * @param FormModel $model
     * @param \SugarBean $contact
     * @param SurveyResultsCollection $results
     */
    public function __construct(FormModel $model, \SugarBean $contact, SurveyResultsCollection $results)
    {
        $this->model = $model;
        $this->contact = $contact;
        $this->surveyResults = $results;
    }

    /**
     * @return FormModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return \SugarBean
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return SurveyResultsCollection
     */
    public function getSurveyResults()
    {
        return $this->surveyResults;
    }
}