<?php namespace squiz\surveys\constraints;

use Symfony\Component\Validator\Constraint;

class StepExistsInSurvey extends Constraint
{
    /**
     * @var \squiz\surveys\models\FormModel $model
     */
    public $model;

    public function getRequiredOptions()
    {
        return array('model');
    }
}