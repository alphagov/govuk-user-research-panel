<?php namespace squiz\surveys\constraints;

use squiz\surveys\helpers\SurveyHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OtherFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = (array)$value;
        $val = array_filter($value);
        foreach ($val as $key => $v) {
            $otherFieldName = SurveyHelper::getOtherFieldName($constraint->field->field, $v);
            //"Other" checkbox is checked and has a related field to enter Other option
            //but other option is empty
            if ($otherFieldName && !$constraint->form_data[$otherFieldName]) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}