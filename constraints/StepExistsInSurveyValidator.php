<?php namespace squiz\surveys\constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class StepExistsInSurveyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $model = $constraint->model;
        $stepExists = false;
        foreach ($model->getSteps() as $stepData) {
            if ($stepData->id == $value) {
                $stepExists = true;
            }
        }

        if (!$stepExists) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}