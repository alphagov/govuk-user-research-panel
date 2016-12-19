<?php namespace squiz\surveys\lib;

use squiz\surveys\entities\SurveyResultsCollection;

/**
 * Interface SurveyResultsFormatterInterface
 * @package squiz\surveys\lib
 */
interface SurveyResultsFormatterInterface
{
    /**
     * SurveyResultsFormatterInterface constructor.
     * @param SurveyResultsCollection $collection
     */
    public function __construct(SurveyResultsCollection $collection);

    /**
     * Format results
     * @return string
     */
    public function format();
}