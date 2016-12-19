<?php namespace squiz\surveys\constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}