<?php namespace squiz\surveys\constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class BirthDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $datetime = \DateTime::createFromFormat('Y-m-d', $value);
        $date_errors = \DateTime::getLastErrors();
        //date is more than 120 years ago or has invalid format - populate error
        if ($date_errors['error_count'] ||
            $date_errors['warning_count'] ||
            $datetime->diff(new \DateTime('now'))->y > 120
        ) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}