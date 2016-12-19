<?php namespace squiz\surveys\lib;

use squiz\surveys\entities\SurveyStepsCollection;
use squiz\surveys\exceptions\StepNotFoundException;

/**
 * Class SurveyStepResolver
 * @package squiz\surveys\lib
 */
class SurveyStepResolver
{
    /**
     * @var array
     */
    protected $steps;

    /**
     * SurveyStepResolver constructor.
     * @param SurveyStepsCollection $steps
     */
    public function __construct(SurveyStepsCollection $steps)
    {
        $this->steps = $steps;
    }

    /**
     * @return array
     */
    private function getStepOrders()
    {
        $allOrders = array();
        foreach ($this->steps as $stepBean) {
            $allOrders[$stepBean->step_order] = $stepBean->step_order;
        }
        return array_values($allOrders);
    }

    /**
     * @param  int $stepKey
     * @param \SugarBean $context
     * @throws StepNotFoundException
     * @return \squiz_SurveyPages
     */
    public function getCurrentStepByStepKey($stepKey, \SugarBean $context)
    {
        foreach ($this->steps as $stepBean) {
            if ($stepBean->step_order != $stepKey) {
                continue;
            }

            //if current step has a condition to be displayed we need to check it
            if (!empty($stepBean->display_condition)) {
                //parse sugar logic - check condition against current context
                $condition = html_entity_decode($stepBean->display_condition);
                $res = \Parser::evaluate($condition, $context)->evaluate();
                //condition is true - this will be a current step
                if ((string)$res == 'true') {
                    return $stepBean;
                }
            } else {
                return $stepBean;
            }
        }
        throw new StepNotFoundException('Step ' . $stepKey . ' has not been found');
    }

    /**
     * Get number of the next step
     * @param int $currentStepKey
     * @return mixed
     */
    public function getNextStepKey($currentStepKey)
    {
        $allOrders = $this->getStepOrders();
        $orderKey = array_search($currentStepKey, $allOrders);

        return (isset($allOrders[$orderKey+1]) ? $allOrders[$orderKey+1] : false);
    }

    /**
     * Get number of the previous step
     * @param int $currentStepKey
     * @return mixed
     */
    public function getPreviousStepKey($currentStepKey)
    {
        $allOrders = $this->getStepOrders();
        $orderKey = array_search($currentStepKey, $allOrders);

        return (isset($allOrders[$orderKey-1]) ? $allOrders[$orderKey-1] : false);
    }
}