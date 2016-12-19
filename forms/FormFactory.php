<?php namespace squiz\surveys\forms;

use squiz\surveys\exceptions\FormNotFoundException;

/**
 * Class FormFactory
 * @package squiz\surveys\forms
 */
class FormFactory
{
    /**
     * @param $name
     * @return mixed
     * @throws FormNotFoundException
     */
    public static function getForm($name)
    {
        $formClass = 'squiz\\surveys\\forms\\'.$name;
        if (class_exists($formClass)) {
            $formObj = new $formClass;
            return $formObj->getForm();
        } else {
            throw new FormNotFoundException();
        }
    }

}