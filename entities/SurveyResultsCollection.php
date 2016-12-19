<?php namespace squiz\surveys\entities;

/**
 * Class SurveyResultsCollection
 * @package squiz\surveys\entities
 */
class SurveyResultsCollection extends GenericCollection
{
    /**
     * @param SurveyResultInterface $item
     */
    public function addItem(SurveyResultInterface $item)
    {
        parent::addItem($item);
    }
}