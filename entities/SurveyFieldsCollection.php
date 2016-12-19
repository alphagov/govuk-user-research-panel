<?php namespace squiz\surveys\entities;

/**
 * Class SurveyFieldsCollection
 * @package squiz\surveys\entities
 */
class SurveyFieldsCollection extends GenericCollection
{
    /**
     * @param \squiz_SurveyQuestions $item
     */
    public function addItem(\squiz_SurveyQuestions $item)
    {
        parent::addItem($item);
    }
}