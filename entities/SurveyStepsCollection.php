<?php namespace squiz\surveys\entities;

/**
 * Class SurveyStepsCollection
 * @package squiz\surveys\entities
 */
class SurveyStepsCollection extends GenericCollection
{
    /**
     * @param \squiz_SurveyPages $item
     * @void
     */
    public function addItem(\squiz_SurveyPages $item)
    {
        parent::addItem($item);
    }
}