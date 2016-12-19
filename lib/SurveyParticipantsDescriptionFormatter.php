<?php namespace squiz\surveys\lib;

use squiz\surveys\entities\SurveyResultsCollection;

/**
 * Class SurveyParticipantsDescriptionFormatter
 * @package squiz\surveys\lib
 */
class SurveyParticipantsDescriptionFormatter implements SurveyResultsFormatterInterface
{
    /**
     * @var SurveyResultsCollection
     */
    protected $collection;

    /**
     * SurveyParticipantsDescriptionFormatter constructor.
     * @param SurveyResultsCollection $collection
     */
    public function __construct(SurveyResultsCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Format results
     * @return string
     */
    public function format()
    {
        $description = '';

        foreach ($this->collection as $result) {
            $description.= "\n".$result->getLabel().":\n";
            $val = (array)$result->getValue();
            foreach ($val as $key => $value) {
                $description.= $value."\n";
            }
        }

        return $description;
    }
}