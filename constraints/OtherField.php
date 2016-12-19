<?php namespace squiz\surveys\constraints;

use Symfony\Component\Validator\Constraint;

class OtherField extends Constraint
{
    public $field;
    public $message;
    public $form_data;

    public function getRequiredOptions()
    {
        return array('field', 'form_data');
    }
}