<?php namespace squiz\surveys\helpers;

use Symfony\Component\Form\FormInterface;

class FormHelper
{
    /**
     * @param FormInterface $form
     * @return array
     */
    public static function getErrorsAsAssociativeArray(FormInterface $form)
    {
        $errors = [];
        foreach ($form->all() as $field) {
            if ($field->getErrors()->count() > 0) {
                $fieldName = $field->getName();
                $errors[$fieldName]['messages'] = [];
                $errors[$fieldName]['value'] = $form->get($fieldName)->getData();
                foreach ($field->getErrors() as $error) {
                    $errors[$fieldName]['messages'][] = $error->getMessage();
                }
            }
        }
        return $errors;
    }
}